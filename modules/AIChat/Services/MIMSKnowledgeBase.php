<?php
/**
 * MIMS Knowledge Base - ฐานความรู้จาก MIMS Pharmacy Thailand 2023
 * 
 * ใช้เป็น Reference สำหรับ AI เภสัชกรออนไลน์
 * ครอบคลุม: ระบบผิวหนัง, ภูมิแพ้, กระดูก-กล้ามเนื้อ, ระบบประสาท, ทางเดินอาหาร, ทางเดินหายใจ, สุขภาพสตรี, ตา-ช่องปาก
 */

namespace Modules\AIChat\Services;

class MIMSKnowledgeBase
{
    /**
     * ฐานข้อมูลโรคและอาการตาม MIMS 2023
     */
    private array $diseaseDatabase = [];
    
    /**
     * Red Flags - อาการที่ต้องส่งต่อแพทย์ทันที
     */
    private array $redFlags = [];
    
    /**
     * ตาราง Topical Corticosteroids Potency (7 ระดับ)
     */
    private array $corticosteroidPotency = [];
    
    public function __construct()
    {
        $this->initializeDiseaseDatabase();
        $this->initializeRedFlags();
        $this->initializeCorticosteroidPotency();
    }
    
    /**
     * ค้นหาข้อมูลโรคจากอาการ
     */
    public function searchBySymptom(string $symptom): array
    {
        $results = [];
        $symptomLower = mb_strtolower($symptom);
        
        foreach ($this->diseaseDatabase as $category => $diseases) {
            foreach ($diseases as $diseaseKey => $disease) {
                // ค้นหาจาก keywords
                foreach ($disease['keywords'] ?? [] as $keyword) {
                    if (mb_strpos($symptomLower, mb_strtolower($keyword)) !== false) {
                        $results[] = array_merge($disease, [
                            'category' => $category,
                            'disease_key' => $diseaseKey,
                            'match_keyword' => $keyword
                        ]);
                        break;
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * ดึงข้อมูลโรคตาม key
     */
    public function getDisease(string $category, string $diseaseKey): ?array
    {
        return $this->diseaseDatabase[$category][$diseaseKey] ?? null;
    }
    
    /**
     * ดึง Red Flags สำหรับโรค
     */
    public function getRedFlags(string $diseaseKey): array
    {
        return $this->redFlags[$diseaseKey] ?? [];
    }
    
    /**
     * ตรวจสอบว่าอาการเข้าข่าย Red Flag หรือไม่
     */
    public function checkRedFlags(string $symptomText): array
    {
        $foundFlags = [];
        $textLower = mb_strtolower($symptomText);
        
        foreach ($this->redFlags as $disease => $flags) {
            foreach ($flags as $flag) {
                foreach ($flag['keywords'] ?? [] as $keyword) {
                    if (mb_strpos($textLower, mb_strtolower($keyword)) !== false) {
                        $foundFlags[] = [
                            'disease' => $disease,
                            'flag' => $flag,
                            'matched_keyword' => $keyword
                        ];
                    }
                }
            }
        }
        
        return $foundFlags;
    }
    
    /**
     * ดึงข้อมูล Corticosteroid ตามระดับความแรง
     */
    public function getCorticosteroidByPotency(int $level): array
    {
        return $this->corticosteroidPotency[$level] ?? [];
    }
    
    /**
     * แนะนำ Corticosteroid ตามบริเวณที่ใช้
     */
    public function recommendCorticosteroid(string $bodyArea): array
    {
        $recommendations = [
            'face' => ['level' => [6, 7], 'note' => 'ใช้ยาความแรงต่ำ (ระดับ 6-7) สำหรับใบหน้า'],
            'armpit' => ['level' => [6, 7], 'note' => 'ใช้ยาความแรงต่ำ (ระดับ 6-7) สำหรับรักแร้'],
            'groin' => ['level' => [6, 7], 'note' => 'ใช้ยาความแรงต่ำ (ระดับ 6-7) สำหรับขาหนีบ'],
            'body' => ['level' => [4, 5], 'note' => 'ใช้ยาความแรงปานกลาง (ระดับ 4-5) สำหรับลำตัว'],
            'arms' => ['level' => [4, 5], 'note' => 'ใช้ยาความแรงปานกลาง (ระดับ 4-5) สำหรับแขน'],
            'legs' => ['level' => [4, 5], 'note' => 'ใช้ยาความแรงปานกลาง (ระดับ 4-5) สำหรับขา'],
            'palms' => ['level' => [1, 2, 3], 'note' => 'ใช้ยาความแรงสูง (ระดับ 1-3) สำหรับฝ่ามือ'],
            'soles' => ['level' => [1, 2, 3], 'note' => 'ใช้ยาความแรงสูง (ระดับ 1-3) สำหรับฝ่าเท้า'],
            'scalp' => ['level' => [2, 3, 4], 'note' => 'ใช้ยาความแรงปานกลาง-สูง (ระดับ 2-4) สำหรับหนังศีรษะ'],
        ];
        
        $areaLower = mb_strtolower($bodyArea);
        
        // Map Thai to English
        $thaiMap = [
            'หน้า' => 'face', 'ใบหน้า' => 'face',
            'รักแร้' => 'armpit',
            'ขาหนีบ' => 'groin',
            'ลำตัว' => 'body', 'ตัว' => 'body',
            'แขน' => 'arms',
            'ขา' => 'legs',
            'ฝ่ามือ' => 'palms', 'มือ' => 'palms',
            'ฝ่าเท้า' => 'soles', 'เท้า' => 'soles',
            'หนังศีรษะ' => 'scalp', 'ศีรษะ' => 'scalp',
        ];
        
        foreach ($thaiMap as $thai => $eng) {
            if (mb_strpos($areaLower, $thai) !== false) {
                $rec = $recommendations[$eng];
                $drugs = [];
                foreach ($rec['level'] as $level) {
                    $drugs = array_merge($drugs, $this->corticosteroidPotency[$level] ?? []);
                }
                return [
                    'area' => $bodyArea,
                    'recommendation' => $rec,
                    'drugs' => $drugs
                ];
            }
        }
        
        return ['area' => $bodyArea, 'recommendation' => null, 'drugs' => []];
    }

    
    /**
     * Initialize Disease Database จาก MIMS 2023
     */
    private function initializeDiseaseDatabase(): void
    {
        // ===== 2.1 ระบบผิวหนัง (Dermatological System) =====
        $this->diseaseDatabase['dermatology'] = [
            'acne' => [
                'name_th' => 'สิว',
                'name_en' => 'Acne',
                'keywords' => ['สิว', 'สิวอุดตัน', 'สิวอักเสบ', 'สิวหัวช้าง', 'acne', 'pimple'],
                'assessment_questions' => [
                    'ลักษณะของสิว: เป็นสิวอุดตัน (Comedones), สิวอักเสบชนิดตุ่มแดง (Papules) หรือตุ่มหนอง (Pustules)?',
                    'ความรุนแรง: มีสิวอักเสบจำนวนมากหรือไม่? มีลักษณะเป็นก้อนไตแข็ง (Nodules) หรือสิวหัวช้าง (Cysts)?',
                    'ประวัติการรักษา: เคยใช้ยารักษาสิวชนิดใดมาก่อน?',
                    'ปัจจัยกระตุ้น: มีการเปลี่ยนแปลงของฮอร์โมน ความเครียด หรือการใช้เครื่องสำอาง?'
                ],
                'non_drug_advice' => [
                    'ล้างหน้าวันละ 2 ครั้งด้วยผลิตภัณฑ์ทำความสะอาดที่อ่อนโยน',
                    'หลีกเลี่ยงการขัด ถู หรือบีบแกะสิว',
                    'เลือกใช้ผลิตภัณฑ์ที่ระบุว่า "non-comedogenic"',
                    'ทำความสะอาดโทรศัพท์มือถือ ปลอกหมอนบ่อยๆ',
                    'จัดการความเครียดและพักผ่อนให้เพียงพอ'
                ],
                'treatments' => [
                    'topical' => [
                        ['name' => 'Adapalene', 'type' => 'Retinoid', 'indication' => 'สิวอุดตัน, สิวอักเสบเล็กน้อย-ปานกลาง'],
                        ['name' => 'Tretinoin', 'type' => 'Retinoid', 'indication' => 'สิวอุดตัน, สิวอักเสบเล็กน้อย-ปานกลาง'],
                        ['name' => 'Benzoyl Peroxide', 'type' => 'Antibacterial', 'indication' => 'สิวอักเสบ, ใช้ร่วมกับยาปฏิชีวนะ'],
                        ['name' => 'Clindamycin', 'type' => 'Topical Antibiotic', 'indication' => 'สิวอักเสบ (ใช้ร่วมกับ Benzoyl Peroxide)'],
                        ['name' => 'Azelaic Acid', 'type' => 'Multi-action', 'indication' => 'สิวอักเสบและรอยดำจากสิว']
                    ],
                    'systemic' => [
                        ['name' => 'Doxycycline', 'type' => 'Systemic Antibiotic', 'indication' => 'สิวอักเสบปานกลาง-รุนแรง'],
                        ['name' => 'Oral Isotretinoin', 'type' => 'Retinoid', 'indication' => 'สิวอักเสบรุนแรง Nodulocystic (สั่งโดยแพทย์)']
                    ]
                ],
                'pharmacist_notes' => 'การใช้ยาปฏิชีวนะชนิดทาเพียงอย่างเดียวมีความเสี่ยงต่อการเกิดเชื้อดื้อยา ควรแนะนำให้ใช้ร่วมกับ Benzoyl Peroxide',
                'referral_criteria' => [
                    'สิวอักเสบรุนแรง เป็นก้อนไตแข็งหรือสิวหัวช้าง (Nodulocystic acne)',
                    'มีแผลเป็นจำนวนมาก',
                    'ไม่ตอบสนองต่อการรักษาเบื้องต้นหลังใช้ยาอย่างต่อเนื่อง',
                    'สงสัยว่าสิวมีสาเหตุจากโรคอื่นหรือยา',
                    'ผู้ป่วยมีความกังวลใจอย่างมากจนส่งผลกระทบต่อคุณภาพชีวิต'
                ]
            ],
            
            'atopic_dermatitis' => [
                'name_th' => 'ภาวะผื่นผิวหนังอักเสบ',
                'name_en' => 'Atopic Dermatitis',
                'keywords' => ['ผื่นแพ้', 'ผิวหนังอักเสบ', 'eczema', 'atopic', 'ผื่นคัน', 'ผิวแห้งคัน'],
                'assessment_questions' => [
                    'อาการหลัก: มีอาการคันและผิวแห้งเป็นหลักหรือไม่?',
                    'ลักษณะผื่น: ผื่นแดง ตุ่มน้ำใส หรือมีน้ำเหลืองซึม (เฉียบพลัน) หรือผื่นหนา แข็ง (เรื้อรัง)?',
                    'ตำแหน่ง: ผื่นขึ้นบริเวณข้อพับแขน ขา ใบหน้า หรือลำคอ?',
                    'ประวัติ: มีประวัติเป็นโรคภูมิแพ้อื่นๆ (หอบหืด, จมูกอักเสบจากภูมิแพ้)?',
                    'การดำเนินโรค: อาการเป็นๆ หายๆ เรื้อรังหรือไม่?'
                ],
                'non_drug_advice' => [
                    'ใช้สารให้ความชุ่มชื้น (Emollients) เป็นประจำ โดยเฉพาะหลังอาบน้ำ',
                    'อาบน้ำด้วยน้ำอุณหภูมิปกติ ใช้สบู่อ่อนๆ',
                    'สวมใส่เสื้อผ้าที่ทำจากผ้าฝ้าย ระบายอากาศได้ดี',
                    'หลีกเลี่ยงสารที่ก่อให้เกิดการระคายเคือง เช่น น้ำหอม สารเคมีรุนแรง',
                    'หลีกเลี่ยงการเปลี่ยนแปลงอุณหภูมิที่รวดเร็ว และจัดการความเครียด'
                ],
                'treatments' => [
                    'topical' => [
                        ['name' => 'Hydrocortisone 1%', 'potency' => 7, 'indication' => 'อาการเล็กน้อย, บริเวณผิวบอบบาง'],
                        ['name' => 'Desonide 0.05%', 'potency' => 6, 'indication' => 'อาการเล็กน้อย-ปานกลาง'],
                        ['name' => 'Mometasone furoate 0.1%', 'potency' => 4, 'indication' => 'อาการปานกลาง'],
                        ['name' => 'Betamethasone valerate 0.1%', 'potency' => 5, 'indication' => 'อาการปานกลาง']
                    ],
                    'emollients' => [
                        ['name' => 'Moisturizing cream/lotion', 'indication' => 'ใช้เป็นประจำทุกวัน'],
                        ['name' => 'Petroleum jelly', 'indication' => 'กักเก็บความชุ่มชื้น']
                    ]
                ],
                'ftu_guide' => 'FTU (Fingertip Unit) = ปริมาณยาจากปลายนิ้วชี้ถึงข้อแรก ≈ 0.5 กรัม เพียงพอสำหรับทาผิวหนัง 2 ฝ่ามือ',
                'referral_criteria' => [
                    'อาการรุนแรงมาก ไม่สามารถควบคุมได้ด้วยยาที่จำหน่ายในร้านยา',
                    'สงสัยว่ามีการติดเชื้อแบคทีเรียแทรกซ้อน (ตุ่มหนอง น้ำเหลืองเกรอะกรัง)',
                    'อาการผื่นกระจายทั่วร่างกาย',
                    'ผู้ป่วยเป็นเด็กทารกหรือเด็กเล็กที่มีอาการรุนแรง'
                ]
            ],
            
            'urticaria' => [
                'name_th' => 'ลมพิษ',
                'name_en' => 'Urticaria',
                'keywords' => ['ลมพิษ', 'ผื่นคัน', 'ผื่นนูนแดง', 'urticaria', 'hives', 'แพ้'],
                'assessment_questions' => [
                    'ลักษณะผื่น: เป็นผื่นนูนแดง มีขอบเขตชัดเจน (Wheals) เกิดขึ้นและหายไปภายใน 24 ชม.?',
                    'อาการร่วม: มีอาการบวมที่เปลือกตาหรือริมฝีปาก (Angioedema)?',
                    'ระยะเวลา: เป็นลมพิษเฉียบพลัน (<6 สัปดาห์) หรือเรื้อรัง (>6 สัปดาห์)?',
                    'ปัจจัยกระตุ้น: สงสัยว่ามีสาเหตุจากอาหาร ยา หรือการติดเชื้อ?'
                ],
                'non_drug_advice' => [
                    'หลีกเลี่ยงปัจจัยกระตุ้นที่สงสัยว่าเป็นสาเหตุ',
                    'ประคบเย็นเพื่อช่วยลดอาการคัน',
                    'หลีกเลี่ยงการเกา เพราะจะยิ่งกระตุ้นให้เกิดผื่นมากขึ้น'
                ],
                'treatments' => [
                    'antihistamines_2nd_gen' => [
                        ['name' => 'Cetirizine', 'dose' => '10 mg วันละครั้ง', 'sedation' => 'น้อย'],
                        ['name' => 'Loratadine', 'dose' => '10 mg วันละครั้ง', 'sedation' => 'น้อยมาก'],
                        ['name' => 'Desloratadine', 'dose' => '5 mg วันละครั้ง', 'sedation' => 'น้อยมาก'],
                        ['name' => 'Levocetirizine', 'dose' => '5 mg วันละครั้ง', 'sedation' => 'น้อย'],
                        ['name' => 'Fexofenadine', 'dose' => '180 mg วันละครั้ง', 'sedation' => 'น้อยมาก'],
                        ['name' => 'Bilastine', 'dose' => '20 mg วันละครั้ง', 'sedation' => 'น้อยมาก']
                    ],
                    'antihistamines_1st_gen' => [
                        ['name' => 'Chlorpheniramine', 'dose' => '4 mg ทุก 4-6 ชม.', 'sedation' => 'มาก'],
                        ['name' => 'Hydroxyzine', 'dose' => '25 mg ทุก 6-8 ชม.', 'sedation' => 'มาก'],
                        ['name' => 'Cyproheptadine', 'dose' => '4 mg วันละ 3 ครั้ง', 'sedation' => 'มาก']
                    ]
                ],
                'pharmacist_notes' => 'แนะนำยาแก้แพ้รุ่นที่ 2 เป็นทางเลือกแรก เนื่องจากไม่ทำให้ง่วงหรือง่วงน้อย',
                'referral_criteria' => [
                    'ส่งต่อทันที: หากมีสัญญาณของภาวะแพ้รุนแรง (Anaphylaxis) - หายใจลำบาก เสียงแหบ ความดันโลหิตต่ำ',
                    'มีอาการบวมที่ปากหรือตา (Angioedema)',
                    'เป็นลมพิษเรื้อรัง (นานกว่า 6 สัปดาห์)',
                    'อาการไม่ตอบสนองต่อการรักษาด้วยยาแก้แพ้'
                ]
            ],
            
            'dry_skin' => [
                'name_th' => 'ภาวะผิวแห้ง',
                'name_en' => 'Dry Skin',
                'keywords' => ['ผิวแห้ง', 'ผิวลอก', 'ผิวแตก', 'dry skin', 'xerosis'],
                'assessment_questions' => [
                    'ลักษณะอาการ: ผิวแห้ง ตกสะเก็ด แตก หรือมีอาการคันร่วมด้วย?',
                    'ปัจจัยแวดล้อม: อาการเป็นมากขึ้นในช่วงอากาศแห้งและเย็น?',
                    'พฤติกรรม: อาบน้ำร้อนบ่อย? ใช้สบู่ที่มีความเป็นด่างสูง?',
                    'โรคประจำตัว/ยา: มีโรคไทรอยด์ หรือใช้ยาที่อาจทำให้ผิวแห้ง?'
                ],
                'non_drug_advice' => [
                    'ทาผลิตภัณฑ์ให้ความชุ่มชื้น (Emollients) ทันทีหลังอาบน้ำ',
                    'เลือกใช้ผลิตภัณฑ์ทำความสะอาดที่อ่อนโยน ไม่มีน้ำหอม pH ใกล้เคียงผิว',
                    'หลีกเลี่ยงการอาบน้ำร้อนหรือแช่น้ำนานเกินไป',
                    'ดื่มน้ำให้เพียงพอ',
                    'สวมเสื้อผ้าที่ปกป้องผิวจากสภาพอากาศที่แห้งและเย็น'
                ],
                'treatments' => [
                    'emollients' => [
                        ['name' => 'Moisturizing cream', 'indication' => 'ใช้เป็นประจำทุกวัน'],
                        ['name' => 'Moisturizing lotion', 'indication' => 'ใช้เป็นประจำทุกวัน'],
                        ['name' => 'Petroleum jelly', 'indication' => 'กักเก็บความชุ่มชื้น']
                    ],
                    'topical' => [
                        ['name' => 'Hydrocortisone 1%', 'indication' => 'ใช้ระยะสั้นเมื่อมีอาการอักเสบและคัน']
                    ]
                ],
                'referral_criteria' => [
                    'อาการผิวแห้งรุนแรงและไม่ตอบสนองต่อการดูแลตนเอง',
                    'สงสัยว่ามีภาวะติดเชื้อที่ผิวหนังแทรกซ้อน',
                    'อาการผิวแห้งอาจเป็นสัญญาณของโรคทางระบบอื่นๆ'
                ]
            ]
        ];

        
        // ===== 2.2 ระบบภูมิคุ้มกันและภูมิแพ้ (Allergy & Immune System) =====
        $this->diseaseDatabase['allergy'] = [
            'allergic_rhinitis' => [
                'name_th' => 'จมูกอักเสบจากภูมิแพ้',
                'name_en' => 'Allergic Rhinitis',
                'keywords' => ['แพ้อากาศ', 'จมูกอักเสบ', 'คัดจมูก', 'น้ำมูกไหล', 'จาม', 'allergic rhinitis', 'hay fever'],
                'classification' => [
                    'intermittent' => 'มีอาการ <4 วัน/สัปดาห์ หรือ <4 สัปดาห์',
                    'persistent' => 'มีอาการ >4 วัน/สัปดาห์ และ >4 สัปดาห์'
                ],
                'assessment_questions' => [
                    'ความถี่: มีอาการกี่วันต่อสัปดาห์? เป็นติดต่อกันนานกี่สัปดาห์?',
                    'ความรุนแรง: อาการรบกวนการนอน การใช้ชีวิตประจำวัน หรือการทำงาน/เรียน?',
                    'อาการหลัก: จาม คันจมูก น้ำมูกไหล คัดจมูก?'
                ],
                'non_drug_advice' => [
                    'ไรฝุ่น: ใช้ผ้าปูที่นอนกันไรฝุ่น ซักเครื่องนอนด้วยน้ำร้อน ลดความชื้นในห้องนอน',
                    'ละอองเกสร: ปิดหน้าต่างในช่วงที่มีละอองเกสรสูง',
                    'สัตว์เลี้ยง: หากแพ้ขนสัตว์ ควรเลี้ยงไว้นอกบ้าน',
                    'การล้างจมูกด้วยน้ำเกลือ: ช่วยชะล้างสารก่อภูมิแพ้และเมือก'
                ],
                'treatments' => [
                    'oral_antihistamines' => [
                        ['name' => 'Cetirizine', 'dose' => '10 mg วันละครั้ง'],
                        ['name' => 'Loratadine', 'dose' => '10 mg วันละครั้ง'],
                        ['name' => 'Fexofenadine', 'dose' => '60 mg วันละ 2 ครั้ง หรือ 180 mg วันละครั้ง']
                    ],
                    'intranasal_corticosteroids' => [
                        ['name' => 'Mometasone furoate', 'indication' => 'ประสิทธิภาพสูงสุดในการควบคุมอาการทุกอาการ'],
                        ['name' => 'Fluticasone furoate', 'indication' => 'ประสิทธิภาพสูงสุดในการควบคุมอาการทุกอาการ'],
                        ['name' => 'Budesonide', 'indication' => 'ประสิทธิภาพสูงสุดในการควบคุมอาการทุกอาการ']
                    ],
                    'decongestants' => [
                        ['name' => 'Pseudoephedrine', 'type' => 'oral', 'warning' => 'ระวังในผู้ป่วยความดันโลหิตสูง'],
                        ['name' => 'Oxymetazoline', 'type' => 'nasal', 'warning' => 'ไม่ควรใช้ติดต่อกันเกิน 3-5 วัน (Rebound congestion)'],
                        ['name' => 'Xylometazoline', 'type' => 'nasal', 'warning' => 'ไม่ควรใช้ติดต่อกันเกิน 3-5 วัน (Rebound congestion)']
                    ]
                ],
                'pharmacist_notes' => 'ยาพ่นจมูกกลุ่ม Intranasal Corticosteroids มีประสิทธิภาพสูงสุด แต่ต้องใช้เวลาหลายวันกว่าจะเห็นผลเต็มที่',
                'referral_criteria' => [
                    'อาการไม่ดีขึ้นหรือไม่สามารถควบคุมได้หลังจากการใช้ยาอย่างถูกต้อง',
                    'สงสัยว่ามีภาวะแทรกซ้อน เช่น ไซนัสอักเสบ หรือหูชั้นกลางอักเสบ',
                    'มีอาการข้างเคียงจากยาที่รบกวนการใช้ชีวิต',
                    'ต้องการทดสอบภูมิแพ้ (Allergy testing) หรือพิจารณา Immunotherapy'
                ]
            ]
        ];
        
        // ===== 2.3 ระบบกระดูกและกล้ามเนื้อ (Musculo-Skeletal System) =====
        $this->diseaseDatabase['musculoskeletal'] = [
            'arthritis' => [
                'name_th' => 'ข้ออักเสบ',
                'name_en' => 'Arthritis',
                'keywords' => ['ปวดข้อ', 'ข้ออักเสบ', 'ข้อบวม', 'arthritis', 'joint pain'],
                'assessment_questions' => [
                    'ลักษณะข้อที่ปวด: ปวดข้อเดียว หรือหลายข้อ? หากหลายข้อ เป็นแบบสมมาตรสองข้าง?',
                    'อาการข้อติดตอนเช้า (Morning stiffness): มีอาการหรือไม่? นานเท่าใด? (>1 ชม. หรือ <30 นาที)',
                    'ตำแหน่งของข้อที่ปวด: เป็นข้อเล็กๆ (นิ้วมือ นิ้วเท้า) หรือข้อใหญ่ที่รับน้ำหนัก (เข่า สะโพก)?'
                ],
                'differential_diagnosis' => [
                    'rheumatoid_arthritis' => 'ปวดหลายข้อสมมาตร, ข้อติดตอนเช้า >1 ชม., มักเกิดที่ข้อนิ้วมือและข้อมือ',
                    'gout' => 'ปวดข้อเดียวเฉียบพลัน โดยเฉพาะข้อนิ้วหัวแม่เท้า',
                    'osteoarthritis' => 'ปวดข้อใหญ่ที่รับน้ำหนัก, ข้อติดตอนเช้า <30 นาที'
                ],
                'non_drug_advice' => [
                    'รูมาตอยด์: แนะนำให้พบแพทย์เพื่อรับการวินิจฉัยและรักษาด้วย DMARDs',
                    'เกาต์: พักการใช้ข้อ ประคบเย็น หลีกเลี่ยงอาหารที่มีพิวรีนสูง',
                    'ข้อเสื่อม: ลดน้ำหนัก ออกกำลังกายเพื่อเสริมสร้างกล้ามเนื้อรอบข้อ'
                ],
                'treatments' => [
                    'nsaids' => [
                        ['name' => 'Ibuprofen', 'dose' => '200-400 mg ทุก 4-6 ชม.', 'gi_risk' => 'ปานกลาง'],
                        ['name' => 'Naproxen', 'dose' => '250-500 mg วันละ 2 ครั้ง', 'gi_risk' => 'ปานกลาง-สูง'],
                        ['name' => 'Diclofenac', 'dose' => '25-50 mg วันละ 2-3 ครั้ง', 'gi_risk' => 'สูง'],
                        ['name' => 'Meloxicam', 'dose' => '7.5-15 mg วันละครั้ง', 'gi_risk' => 'ต่ำกว่า NSAIDs ดั้งเดิม'],
                        ['name' => 'Celecoxib', 'dose' => '100-200 mg วันละ 1-2 ครั้ง', 'gi_risk' => 'ต่ำ (COX-2 selective)']
                    ]
                ],
                'pharmacist_notes' => 'ก่อนแนะนำ NSAIDs ควรประเมินความเสี่ยงต่อระบบทางเดินอาหาร (GI risk) และระบบหัวใจและหลอดเลือด (CV risk)',
                'referral_criteria' => [
                    'สงสัยข้ออักเสบรูมาตอยด์ (ปวดหลายข้อสมมาตร, ข้อติดตอนเช้านาน)',
                    'อาการปวดจากเกาต์ครั้งแรก หรือควบคุมอาการไม่ได้',
                    'อาการปวดข้อเรื้อรังที่ยังไม่ได้รับการวินิจฉัย',
                    'มีอาการปวดข้อร่วมกับอาการทางระบบอื่นๆ เช่น มีไข้ อ่อนเพลีย'
                ]
            ],
            
            'muscle_pain' => [
                'name_th' => 'ปวดกล้ามเนื้อ',
                'name_en' => 'Muscle Pain',
                'keywords' => ['ปวดกล้ามเนื้อ', 'ปวดเมื่อย', 'กล้ามเนื้ออักเสบ', 'muscle pain', 'myalgia'],
                'assessment_questions' => [
                    'สาเหตุ: อาการปวดเกิดหลังจากการออกกำลังกายหรือการใช้งานหนัก? มีอุบัติเหตุหรือการบาดเจ็บ?',
                    'ลักษณะอาการปวด: ปวดตื้อๆ ปวดแปลบ หรือปวดร้าวไปที่อื่น?',
                    'สัญญาณอันตราย: มีอาการบวมผิดรูป, ไม่สามารถขยับหรือลงน้ำหนักได้, มีอาการชาหรืออ่อนแรง?'
                ],
                'non_drug_advice' => [
                    'หลัก PRICE ในช่วง 24-72 ชม.แรก:',
                    'P (Protection): ป้องกันการบาดเจ็บซ้ำ',
                    'R (Rest): พักการใช้งานส่วนที่บาดเจ็บ',
                    'I (Ice): ประคบเย็น 15-20 นาที ทุก 2-3 ชม.',
                    'C (Compression): พันผ้ายืดเพื่อลดบวม',
                    'E (Elevation): ยกส่วนที่บาดเจ็บให้สูงกว่าระดับหัวใจ'
                ],
                'treatments' => [
                    'oral' => [
                        ['name' => 'Paracetamol', 'dose' => '500-1,000 mg ทุก 4-6 ชม.'],
                        ['name' => 'Ibuprofen', 'dose' => '200-400 mg ทุก 4-6 ชม.'],
                        ['name' => 'Naproxen', 'dose' => '250-500 mg วันละ 2 ครั้ง']
                    ],
                    'topical' => [
                        ['name' => 'Diclofenac gel', 'indication' => 'ทาบริเวณที่ปวด 3-4 ครั้ง/วัน'],
                        ['name' => 'Piroxicam gel', 'indication' => 'ทาบริเวณที่ปวด 3-4 ครั้ง/วัน'],
                        ['name' => 'Methyl salicylate', 'indication' => 'ทำให้รู้สึกร้อน บรรเทาปวด'],
                        ['name' => 'Menthol', 'indication' => 'ทำให้รู้สึกเย็น บรรเทาปวด']
                    ]
                ],
                'referral_criteria' => [
                    'สงสัยว่ามีกระดูกหักหรือข้อเคลื่อน',
                    'อาการปวดรุนแรงมาก ไม่สามารถควบคุมได้ด้วยยาแก้ปวดทั่วไป',
                    'มีอาการชา อ่อนแรง หรือการรับความรู้สึกผิดปกติ',
                    'อาการไม่ดีขึ้นภายใน 72 ชม.หลังการดูแลตนเอง'
                ]
            ],
            
            'osteoarthritis' => [
                'name_th' => 'ข้อเสื่อม',
                'name_en' => 'Osteoarthritis',
                'keywords' => ['ข้อเสื่อม', 'ปวดเข่า', 'ปวดสะโพก', 'osteoarthritis', 'OA'],
                'assessment_questions' => [
                    'ตำแหน่ง: ปวดข้อที่รับน้ำหนัก เช่น เข่า สะโพก หรือข้อปลายนิ้ว?',
                    'ลักษณะอาการ: ปวดมากขึ้นเมื่อใช้งาน และดีขึ้นเมื่อพัก? มีเสียงกรอบแกรบในข้อ?',
                    'อาการข้อติด: มีอาการข้อติดขัดในตอนเช้า แต่ไม่นานเกิน 30 นาที?',
                    'ปัจจัยเสี่ยง: มีภาวะน้ำหนักเกิน, ประวัติการบาดเจ็บที่ข้อ, หรืออายุมาก?'
                ],
                'non_drug_advice' => [
                    'ลดน้ำหนัก',
                    'ออกกำลังกายแบบแรงกระแทกต่ำ (ว่ายน้ำ, ปั่นจักรยาน)',
                    'อาจพิจารณาใช้ไม้เท้าหรืออุปกรณ์พยุงเข่า',
                    'ประคบร้อนก่อนออกกำลังกาย ประคบเย็นหลังใช้งาน'
                ],
                'treatments' => [
                    'first_line' => [
                        ['name' => 'Paracetamol', 'indication' => 'ยาทางเลือกแรกที่แนะนำเนื่องจากมีความปลอดภัย']
                    ],
                    'nsaids_oral' => [
                        ['name' => 'Ibuprofen', 'indication' => 'ใช้ในขนาดยาต่ำที่สุดและระยะเวลาสั้นที่สุด'],
                        ['name' => 'Naproxen', 'indication' => 'ใช้ในขนาดยาต่ำที่สุดและระยะเวลาสั้นที่สุด'],
                        ['name' => 'Celecoxib', 'indication' => 'ใช้ในขนาดยาต่ำที่สุดและระยะเวลาสั้นที่สุด']
                    ],
                    'nsaids_topical' => [
                        ['name' => 'Diclofenac gel', 'indication' => 'ทางเลือกที่ดีสำหรับข้อที่อยู่ตื้น เช่น ข้อเข่า ข้อมือ'],
                        ['name' => 'Ketoprofen gel', 'indication' => 'ทางเลือกที่ดีสำหรับข้อที่อยู่ตื้น']
                    ],
                    'supplements' => [
                        ['name' => 'Glucosamine sulfate', 'indication' => 'อาจช่วยลดอาการปวดและชะลอการแคบลงของข้อในผู้ป่วยบางราย']
                    ]
                ],
                'referral_criteria' => [
                    'อาการปวดรุนแรงจนรบกวนการใช้ชีวิตประจำวันอย่างมาก',
                    'ไม่สามารถควบคุมอาการได้ด้วยการรักษาเบื้องต้น',
                    'ข้อมีการผิดรูปอย่างเห็นได้ชัด',
                    'ต้องการพิจารณาการรักษาอื่นๆ เช่น การฉีดยาเข้าข้อ หรือการผ่าตัด'
                ]
            ]
        ];

        
        // ===== 2.4 ระบบประสาท (Nervous System) =====
        $this->diseaseDatabase['nervous'] = [
            'migraine' => [
                'name_th' => 'ปวดศีรษะไมเกรน',
                'name_en' => 'Migraine',
                'keywords' => ['ไมเกรน', 'ปวดหัว', 'ปวดศีรษะ', 'migraine', 'headache'],
                'assessment_questions' => [
                    'ลักษณะอาการปวด: ปวดศีรษะตุบๆ ข้างเดียว? ความรุนแรงปานกลางถึงมาก?',
                    'อาการร่วม: มีอาการคลื่นไส้ อาเจียน หรือไวต่อแสง/เสียงมากกว่าปกติ?',
                    'อาการนำ (Aura): มีอาการผิดปกติทางการมองเห็น (เห็นแสงซิกแซก) นำมาก่อนปวดศีรษะ?',
                    'ปัจจัยกระตุ้น: มีปัจจัยกระตุ้นที่ชัดเจน เช่น ความเครียด, การอดนอน, อาหารบางชนิด?'
                ],
                'non_drug_advice' => [
                    'พักผ่อนในห้องที่เงียบและมืด',
                    'ประคบเย็นบริเวณหน้าผากหรือต้นคอ',
                    'จดบันทึกอาการปวดศีรษะ (Headache diary) เพื่อสังเกตและหลีกเลี่ยงปัจจัยกระตุ้น',
                    'รักษาสุขอนามัยการนอนที่ดี',
                    'จัดการความเครียด และรับประทานอาหารให้ตรงเวลา'
                ],
                'treatments' => [
                    'acute' => [
                        ['name' => 'Aspirin', 'type' => 'Analgesic', 'indication' => 'อาการปวดเล็กน้อย-ปานกลาง'],
                        ['name' => 'Ibuprofen', 'type' => 'NSAID', 'indication' => 'อาการปวดเล็กน้อย-ปานกลาง'],
                        ['name' => 'Naproxen', 'type' => 'NSAID', 'indication' => 'อาการปวดเล็กน้อย-ปานกลาง'],
                        ['name' => 'Sumatriptan', 'type' => 'Triptan', 'indication' => 'อาการปวดปานกลาง-รุนแรง (ยาอันตราย)'],
                        ['name' => 'Ergotamine + Caffeine', 'type' => 'Ergot', 'indication' => 'อาการปวดปานกลาง-รุนแรง (ยาอันตราย)']
                    ],
                    'prophylaxis' => [
                        ['name' => 'Propranolol', 'type' => 'Beta-blocker', 'indication' => 'ป้องกันในผู้ที่มีอาการบ่อยและรุนแรง (สั่งโดยแพทย์)'],
                        ['name' => 'Amitriptyline', 'type' => 'TCA', 'indication' => 'ป้องกันในผู้ที่มีอาการบ่อยและรุนแรง (สั่งโดยแพทย์)'],
                        ['name' => 'Topiramate', 'type' => 'Anticonvulsant', 'indication' => 'ป้องกันในผู้ที่มีอาการบ่อยและรุนแรง (สั่งโดยแพทย์)']
                    ]
                ],
                'referral_criteria' => [
                    'ปวดศีรษะรุนแรงเฉียบพลันเหมือนที่ไม่เคยเป็นมาก่อน ("thunderclap headache")',
                    'อาการปวดศีรษะเปลี่ยนไปจากเดิม หรือมีความถี่และความรุนแรงเพิ่มขึ้น',
                    'มีอาการทางระบบประสาทอื่นๆ ร่วมด้วย เช่น แขนขาอ่อนแรง, สับสน, ชัก',
                    'ต้องการยาป้องกันเนื่องจากอาการปวดรบกวนชีวิตประจำวันอย่างมาก'
                ]
            ],
            
            'vertigo' => [
                'name_th' => 'เวียนศีรษะบ้านหมุน',
                'name_en' => 'Vertigo',
                'keywords' => ['เวียนหัว', 'บ้านหมุน', 'มึนงง', 'vertigo', 'dizziness'],
                'assessment_questions' => [
                    'ลักษณะอาการ: เป็นความรู้สึกว่าสิ่งแวดล้อมหมุนหรือตัวเองหมุน (Vertigo) หรือเป็นเพียงอาการมึนงง/โคลงเคลง (Dizziness)?',
                    'ปัจจัยกระตุ้น: อาการเกิดขึ้นเมื่อมีการเปลี่ยนท่าทางของศีรษะ (อาจเป็น BPPV)?',
                    'อาการร่วม: มีอาการคลื่นไส้ อาเจียน, การได้ยินลดลง, หรือมีเสียงในหู (อาจเป็น Meniere\'s disease)?',
                    'สัญญาณอันตราย (Red Flags): มีอาการเห็นภาพซ้อน, พูดไม่ชัด, แขนขาอ่อนแรง, ปวดศีรษะรุนแรง?'
                ],
                'non_drug_advice' => [
                    'เคลื่อนไหวช้าๆ และหลีกเลี่ยงการเปลี่ยนท่าทางอย่างรวดเร็ว',
                    'นอนพักในท่าที่สบายที่สุดเมื่อมีอาการ',
                    'หลีกเลี่ยงการขับรถหรือทำงานกับเครื่องจักรขณะมีอาการ'
                ],
                'treatments' => [
                    'antivertigo' => [
                        ['name' => 'Dimenhydrinate', 'dose' => '50 mg ทุก 4-6 ชม.'],
                        ['name' => 'Cinnarizine', 'dose' => '25-30 mg วันละ 3 ครั้ง'],
                        ['name' => 'Betahistine', 'dose' => '8-16 mg วันละ 3 ครั้ง', 'note' => 'มักใช้ใน Meniere\'s disease'],
                        ['name' => 'Flunarizine', 'dose' => '5-10 mg วันละครั้งก่อนนอน', 'note' => 'มักใช้ป้องกันไมเกรน'],
                        ['name' => 'Meclizine', 'dose' => '25-50 mg ทุก 24 ชม.'],
                        ['name' => 'Promethazine', 'dose' => '25 mg ทุก 4-6 ชม.']
                    ]
                ],
                'referral_criteria' => [
                    'ส่งต่อทันที: หากมีสัญญาณอันตรายทางระบบประสาท (Red Flags) - อาจบ่งชี้ถึงโรคหลอดเลือดสมอง',
                    'อาการเวียนศีรษะเกิดขึ้นครั้งแรกและมีความรุนแรง',
                    'อาการไม่ดีขึ้นหรือไม่ตอบสนองต่อการรักษาเบื้องต้น',
                    'สงสัยว่ามีสาเหตุมาจากโรคที่ซับซ้อน เช่น Meniere\'s disease'
                ]
            ],
            
            'sleep_disorders' => [
                'name_th' => 'ความผิดปกติของการนอน',
                'name_en' => 'Sleep Disorders',
                'keywords' => ['นอนไม่หลับ', 'หลับยาก', 'insomnia', 'sleep disorder'],
                'assessment_questions' => [
                    'ประเภทของปัญหา: นอนไม่หลับ (Insomnia), นอนมากเกินไป (Hypersomnia), หรือมีพฤติกรรมผิดปกติระหว่างนอน?',
                    'สำหรับอาการนอนไม่หลับ: เป็นแบบหลับยาก (Sleep-onset), หลับไม่ต่อเนื่อง (Sleep-maintenance), หรือตื่นเช้ากว่าปกติ?',
                    'ระยะเวลา: เป็นปัญหาชั่วคราว (<3 เดือน) หรือเรื้อรัง (>3 เดือน)?',
                    'ปัจจัยที่เกี่ยวข้อง: มีความเครียด, การเปลี่ยนตารางเวลา (Jet lag), หรือสงสัยภาวะหยุดหายใจขณะหลับ?'
                ],
                'non_drug_advice' => [
                    'สุขอนามัยการนอนหลับที่ดี (Sleep Hygiene):',
                    'เข้านอนและตื่นนอนให้เป็นเวลาเดียวกันทุกวัน',
                    'สร้างบรรยากาศห้องนอนให้เงียบ, มืด, และเย็นสบาย',
                    'หลีกเลี่ยงคาเฟอีน, แอลกอฮอล์, และนิโคตินในช่วงเย็น',
                    'หลีกเลี่ยงการดูหน้าจออุปกรณ์อิเล็กทรอนิกส์ก่อนนอน',
                    'ออกกำลังกายสม่ำเสมอ แต่หลีกเลี่ยงการออกกำลังกายหนักๆ ใกล้เวลานอน'
                ],
                'treatments' => [
                    'benzodiazepines' => [
                        ['name' => 'Diazepam', 'dose' => '5-10 mg', 'warning' => 'เสี่ยงต่อการติดยา, กดการหายใจ, ง่วงซึมในวันถัดไป'],
                        ['name' => 'Lorazepam', 'dose' => '1-2 mg', 'warning' => 'เสี่ยงต่อการติดยา, กดการหายใจ, ง่วงซึมในวันถัดไป']
                    ],
                    'non_benzodiazepines' => [
                        ['name' => 'Zopiclone', 'dose' => '7.5 mg', 'warning' => 'ยังคงมีความเสี่ยงต่อพฤติกรรมผิดปกติระหว่างนอน'],
                        ['name' => 'Zolpidem', 'dose' => '5-10 mg', 'warning' => 'ยังคงมีความเสี่ยงต่อพฤติกรรมผิดปกติระหว่างนอน']
                    ],
                    'antihistamines' => [
                        ['name' => 'Diphenhydramine', 'dose' => '25-50 mg', 'warning' => 'อาจทำให้ปากแห้ง, ท้องผูก และง่วงในวันถัดไป'],
                        ['name' => 'Doxylamine', 'dose' => '25 mg', 'warning' => 'อาจทำให้ปากแห้ง, ท้องผูก และง่วงในวันถัดไป']
                    ]
                ],
                'pharmacist_notes' => 'การใช้ยานอนหลับควรใช้ในระยะสั้นและอยู่ภายใต้การดูแลของแพทย์',
                'referral_criteria' => [
                    'อาการนอนไม่หลับเรื้อรัง (มากกว่า 3 เดือน)',
                    'สงสัยว่ามีภาวะหยุดหายใจขณะหลับ (Obstructive Sleep Apnea)',
                    'อาการไม่ดีขึ้นหลังปรับสุขอนามัยการนอนแล้ว',
                    'ต้องการใช้ยานอนหลับในระยะยาว'
                ]
            ]
        ];
        
        // ===== 2.5 ระบบทางเดินอาหาร (Gastrointestinal System) =====
        $this->diseaseDatabase['gastrointestinal'] = [
            'gerd' => [
                'name_th' => 'โรคกรดไหลย้อน',
                'name_en' => 'GERD',
                'keywords' => ['กรดไหลย้อน', 'แสบร้อนกลางอก', 'heartburn', 'gerd', 'acid reflux'],
                'assessment_questions' => [
                    'อาการหลัก: มีอาการแสบร้อนกลางอก (Heartburn) หรือรู้สึกเปรี้ยวในคอ (Regurgitation)?',
                    'ความถี่: มีอาการบ่อยแค่ไหน (เช่น มากกว่า 2 ครั้ง/สัปดาห์)?',
                    'อาการร่วม: มีอาการอื่นร่วมด้วย เช่น ไอเรื้อรัง กลืนลำบาก เจ็บคอ?',
                    'สัญญาณอันตราย: มีอาการกลืนเจ็บ, น้ำหนักลดโดยไม่ทราบสาเหตุ, อาเจียนเป็นเลือด หรือถ่ายอุจจาระสีดำ?'
                ],
                'non_drug_advice' => [
                    'หลีกเลี่ยงอาหารที่กระตุ้นอาการ เช่น อาหารรสจัด อาหารมัน ของทอด กาแฟ และแอลกอฮอล์',
                    'ไม่ควรรับประทานอาหารมื้อใหญ่ และไม่ควรนอนทันทีหลังรับประทานอาหาร (ควรรออย่างน้อย 3 ชม.)',
                    'ยกศีรษะให้สูงขึ้นเวลานอน',
                    'ลดน้ำหนักหากมีภาวะน้ำหนักเกิน และงดสูบบุหรี่'
                ],
                'treatments' => [
                    'antacids' => [
                        ['name' => 'Aluminium hydroxide', 'indication' => 'ออกฤทธิ์เร็ว สะเทินกรด เหมาะสำหรับอาการที่เป็นครั้งคราว'],
                        ['name' => 'Magnesium hydroxide', 'indication' => 'ออกฤทธิ์เร็ว สะเทินกรด เหมาะสำหรับอาการที่เป็นครั้งคราว'],
                        ['name' => 'Calcium carbonate', 'indication' => 'ออกฤทธิ์เร็ว สะเทินกรด เหมาะสำหรับอาการที่เป็นครั้งคราว']
                    ],
                    'h2ra' => [
                        ['name' => 'Cimetidine', 'indication' => 'ลดการหลั่งกรด ออกฤทธิ์นานกว่ายาลดกรด'],
                        ['name' => 'Famotidine', 'indication' => 'ลดการหลั่งกรด ออกฤทธิ์นานกว่ายาลดกรด']
                    ],
                    'ppi' => [
                        ['name' => 'Omeprazole', 'dose' => '20 mg วันละครั้งก่อนอาหาร 30-60 นาที', 'indication' => 'ยับยั้งการหลั่งกรดอย่างมีประสิทธิภาพสูงสุด'],
                        ['name' => 'Esomeprazole', 'dose' => '20-40 mg วันละครั้งก่อนอาหาร 30-60 นาที', 'indication' => 'ยับยั้งการหลั่งกรดอย่างมีประสิทธิภาพสูงสุด'],
                        ['name' => 'Lansoprazole', 'dose' => '15-30 mg วันละครั้งก่อนอาหาร 30-60 นาที', 'indication' => 'ยับยั้งการหลั่งกรดอย่างมีประสิทธิภาพสูงสุด'],
                        ['name' => 'Pantoprazole', 'dose' => '20-40 mg วันละครั้งก่อนอาหาร 30-60 นาที', 'indication' => 'ยับยั้งการหลั่งกรดอย่างมีประสิทธิภาพสูงสุด']
                    ]
                ],
                'pharmacist_notes' => 'ยาในกลุ่ม PPIs มีประสิทธิภาพสูงสุด แต่การใช้ในระยะยาวควรอยู่ภายใต้การดูแลของแพทย์',
                'referral_criteria' => [
                    'มีสัญญาณอันตราย (กลืนลำบาก/เจ็บ, น้ำหนักลด, อาเจียนเป็นเลือด)',
                    'อาการไม่ดีขึ้นหลังจากใช้ยา PPIs อย่างต่อเนื่องเป็นเวลา 2-4 สัปดาห์',
                    'ผู้ป่วยอายุมากกว่า 55 ปีที่มีอาการเป็นครั้งแรก หรือมีอาการเปลี่ยนแปลงไป',
                    'ต้องการใช้ยาต่อเนื่องเป็นระยะเวลานาน'
                ]
            ]
        ];

        
        // ===== 2.6 ระบบทางเดินหายใจ (Respiratory System) =====
        $this->diseaseDatabase['respiratory'] = [
            'cough' => [
                'name_th' => 'อาการไอ',
                'name_en' => 'Cough',
                'keywords' => ['ไอ', 'ไอแห้ง', 'ไอมีเสมหะ', 'cough', 'ไอเรื้อรัง'],
                'assessment_questions' => [
                    'ประเภทของไอ: เป็นไอแห้ง (Dry cough) หรือไอมีเสมหะ (Productive cough)?',
                    'ลักษณะเสมหะ: เสมหะมีสีอะไร (ใส ขาว เหลือง เขียว)?',
                    'ระยะเวลา: ไอนานเกิน 3 สัปดาห์หรือไม่ (ไอเรื้อรัง)?',
                    'อาการร่วม/สัญญาณอันตราย: มีไข้สูง, หายใจหอบเหนื่อย, เจ็บหน้าอก, หรือน้ำหนักลด?'
                ],
                'non_drug_advice' => [
                    'ดื่มน้ำอุ่นมากๆ เพื่อช่วยให้ชุ่มคอและลดความเหนียวของเสมหะ',
                    'หลีกเลี่ยงสิ่งกระตุ้น เช่น ควันบุหรี่ ฝุ่น และอากาศเย็น',
                    'พักผ่อนให้เพียงพอ'
                ],
                'treatments' => [
                    'dry_cough' => [
                        ['name' => 'Dextromethorphan', 'type' => 'Antitussive', 'mechanism' => 'กดศูนย์ควบคุมการไอในสมอง']
                    ],
                    'productive_cough' => [
                        ['name' => 'Guaifenesin', 'type' => 'Expectorant', 'mechanism' => 'เพิ่มปริมาณสารคัดหลั่ง ลดความเหนียวของเสมหะ'],
                        ['name' => 'Bromhexine', 'type' => 'Mucolytic', 'mechanism' => 'ทำให้โครงสร้างของเสมหะสลายตัว'],
                        ['name' => 'Ambroxol', 'type' => 'Mucolytic', 'mechanism' => 'ทำให้โครงสร้างของเสมหะสลายตัว'],
                        ['name' => 'Carbocysteine', 'type' => 'Mucolytic', 'mechanism' => 'ทำให้โครงสร้างของเสมหะสลายตัว'],
                        ['name' => 'Acetylcysteine', 'type' => 'Mucolytic', 'mechanism' => 'ทำให้โครงสร้างของเสมหะสลายตัว']
                    ]
                ],
                'pharmacist_notes' => 'ไม่ควรใช้ยาระงับอาการไอ (Antitussives) ในผู้ป่วยที่ไอมีเสมหะ เพราะจะทำให้เสมหะคั่งค้าง',
                'referral_criteria' => [
                    'ไอนานเกิน 3 สัปดาห์',
                    'มีสัญญาณอันตราย เช่น หายใจลำบาก, เจ็บหน้าอก, ไอเป็นเลือด, ไข้สูงไม่ลด',
                    'เสมหะมีสีเขียวหรือสีสนิม',
                    'ผู้ป่วยกลุ่มเสี่ยง เช่น เด็กเล็ก ผู้สูงอายุ หรือผู้ที่มีโรคปอดเรื้อรัง'
                ]
            ],
            
            'sore_throat' => [
                'name_th' => 'อาการเจ็บคอ',
                'name_en' => 'Sore Throat',
                'keywords' => ['เจ็บคอ', 'คออักเสบ', 'sore throat', 'pharyngitis'],
                'assessment_questions' => [
                    'ลักษณะอาการ: เจ็บคอมากจนกลืนลำบาก?',
                    'อาการร่วม: มีไข้, ต่อมทอนซิลบวมแดงหรือมีจุดหนอง, ต่อมน้ำเหลืองที่คอโต? (อาจบ่งชี้ถึงการติดเชื้อแบคทีเรีย)',
                    'ระยะเวลา: เจ็บคอมานานกว่า 7 วัน?'
                ],
                'non_drug_advice' => [
                    'กลั้วคอด้วยน้ำเกลืออุ่นๆ',
                    'ดื่มน้ำมากๆ และพักผ่อนให้เพียงพอ',
                    'หลีกเลี่ยงการใช้เสียงดัง'
                ],
                'treatments' => [
                    'analgesics' => [
                        ['name' => 'Paracetamol', 'indication' => 'ลดอาการเจ็บและลดไข้'],
                        ['name' => 'Ibuprofen', 'indication' => 'ลดอาการเจ็บและลดไข้']
                    ],
                    'lozenges' => [
                        ['name' => 'Benzocaine lozenges', 'indication' => 'ยาชาเฉพาะที่ บรรเทาอาการชั่วคราว'],
                        ['name' => 'Amylmetacresol lozenges', 'indication' => 'ยาฆ่าเชื้อ บรรเทาอาการชั่วคราว']
                    ],
                    'sprays' => [
                        ['name' => 'Benzydamine spray', 'indication' => 'ลดการอักเสบและบรรเทาอาการเจ็บ'],
                        ['name' => 'Povidone-iodine spray', 'indication' => 'ฆ่าเชื้อและบรรเทาอาการเจ็บ']
                    ]
                ],
                'referral_criteria' => [
                    'เจ็บคอรุนแรงมาก ร่วมกับมีไข้สูง และต่อมทอนซิลมีหนอง (สงสัย Strep throat)',
                    'มีอาการหายใจลำบากหรือกลืนลำบากมาก',
                    'อาการไม่ดีขึ้นภายใน 7 วัน'
                ]
            ]
        ];
        
        // ===== 2.7 สุขภาพสตรี (Women's Health) =====
        $this->diseaseDatabase['womens_health'] = [
            'vaginitis' => [
                'name_th' => 'ช่องคลอดอักเสบจากการติดเชื้อ',
                'name_en' => 'Vaginitis / Candidiasis',
                'keywords' => ['ตกขาว', 'คันช่องคลอด', 'เชื้อรา', 'candidiasis', 'vaginitis', 'yeast infection'],
                'assessment_questions' => [
                    'ลักษณะของตกขาว: เป็นก้อนคล้ายนมบูด (เชื้อรา), สีเทาขาว มีกลิ่นคาวปลา (แบคทีเรีย), หรือสีเหลืองเขียวเป็นฟอง (โปรโตซัว)?',
                    'อาการร่วม: มีอาการคัน แสบร้อน หรือเจ็บขณะปัสสาวะ/มีเพศสัมพันธ์?',
                    'ประวัติ: เคยเป็นมาก่อน? กำลังตั้งครรภ์ หรือเป็นเบาหวาน?'
                ],
                'non_drug_advice' => [
                    'รักษาสุขอนามัย แต่หลีกเลี่ยงการสวนล้างช่องคลอด',
                    'สวมใส่เสื้อผ้าที่ระบายอากาศได้ดี ไม่รัดแน่น',
                    'หากเป็นเชื้อรา ให้รักษาคู่นอนด้วยหากมีอาการ'
                ],
                'treatments' => [
                    'candidiasis' => [
                        ['name' => 'Clotrimazole', 'form' => 'ยาสอดช่องคลอด, ยาทาภายนอก'],
                        ['name' => 'Miconazole', 'form' => 'ยาสอดช่องคลอด, ยาทาภายนอก'],
                        ['name' => 'Fluconazole', 'form' => 'ยารับประทาน (สั่งโดยแพทย์)']
                    ],
                    'bacterial_vaginosis' => [
                        ['name' => 'Metronidazole', 'form' => 'ยารับประทาน, ยาสอด (สั่งโดยแพทย์)'],
                        ['name' => 'Clindamycin', 'form' => 'ยารับประทาน, ยาสอด (สั่งโดยแพทย์)']
                    ]
                ],
                'pharmacist_notes' => 'สำหรับการติดเชื้อราในช่องคลอดที่ไม่ซับซ้อน เภสัชกรสามารถจ่ายยาต้านเชื้อรากลุ่ม Azole ชนิดสอดได้',
                'referral_criteria' => [
                    'เป็นการติดเชื้อครั้งแรก หรือไม่แน่ใจในสาเหตุ',
                    'สงสัยการติดเชื้อแบคทีเรียหรือโปรโตซัว',
                    'อาการไม่ดีขึ้นหลังการรักษาเชื้อราด้วยตนเอง',
                    'ผู้ป่วยตั้งครรภ์ หรือมีโรคประจำตัว เช่น เบาหวานที่ควบคุมไม่ได้',
                    'มีการติดเชื้อซ้ำบ่อย (มากกว่า 4 ครั้ง/ปี)'
                ]
            ]
        ];
        
        // ===== 2.8 ระบบตา และช่องปาก (Ophthalmology & Oral Health) =====
        $this->diseaseDatabase['eye_oral'] = [
            'dry_eye' => [
                'name_th' => 'ตาแห้ง',
                'name_en' => 'Dry Eye',
                'keywords' => ['ตาแห้ง', 'ตาแสบ', 'ตาล้า', 'dry eye', 'eye strain'],
                'assessment_questions' => [
                    'อาการ: รู้สึกระคายเคือง, แสบตา, เหมือนมีทรายในตา, หรือตาพร่ามัวเป็นพักๆ?',
                    'ปัจจัยเสี่ยง: ใช้คอมพิวเตอร์เป็นเวลานาน, อยู่ในห้องแอร์, ใส่คอนแทคเลนส์, หรือรับประทานยาบางชนิด?',
                    'สัญญาณอันตราย: มีอาการปวดตารุนแรง, การมองเห็นลดลงอย่างชัดเจน, หรือตาสู้แสงไม่ได้?'
                ],
                'non_drug_advice' => [
                    'พักสายตาเป็นระยะ (หลัก 20-20-20: ทุก 20 นาที มองไกล 20 ฟุต นาน 20 วินาที)',
                    'หลีกเลี่ยงลมหรือควันที่พัดเข้าตาโดยตรง',
                    'กะพริบตาให้บ่อยขึ้น'
                ],
                'treatments' => [
                    'artificial_tears' => [
                        ['name' => 'Hypromellose', 'form' => 'Solution', 'indication' => 'เหมาะสำหรับใช้ตอนกลางวัน'],
                        ['name' => 'Carboxymethylcellulose (CMC)', 'form' => 'Solution', 'indication' => 'เหมาะสำหรับใช้ตอนกลางวัน'],
                        ['name' => 'Polyvinyl alcohol', 'form' => 'Solution', 'indication' => 'เหมาะสำหรับใช้ตอนกลางวัน'],
                        ['name' => 'Hyaluronic acid', 'form' => 'Solution', 'indication' => 'เหมาะสำหรับใช้ตอนกลางวัน'],
                        ['name' => 'Eye ointment/gel', 'form' => 'Ointment/Gel', 'indication' => 'ความหนืดสูงกว่า เหมาะสำหรับใช้ก่อนนอน']
                    ]
                ],
                'referral_criteria' => [
                    'มีสัญญาณอันตราย (ปวดตารุนแรง, การมองเห็นลดลง)',
                    'อาการไม่ดีขึ้นหลังจากใช้น้ำตาเทียมอย่างสม่ำเสมอ',
                    'สงสัยว่ามีสาเหตุจากโรคทางกายหรือการติดเชื้อ'
                ]
            ],
            
            'mouth_ulcers' => [
                'name_th' => 'แผลในปาก',
                'name_en' => 'Mouth Ulcers',
                'keywords' => ['แผลร้อนใน', 'แผลในปาก', 'เริม', 'mouth ulcer', 'aphthous ulcer', 'cold sore'],
                'assessment_questions' => [
                    'ลักษณะแผล: เป็นแผลตื้นๆ กลมๆ ขอบแดง ตรงกลางสีขาว/เหลือง (แผลร้อนใน) หรือเป็นกลุ่มของตุ่มน้ำใสที่เจ็บปวดบริเวณริมฝีปาก (เริม)?',
                    'จำนวนและขนาด: มีแผลเดียวหรือหลายแผล? ขนาดใหญ่กว่า 1 ซม.?',
                    'ระยะเวลา: แผลไม่หายภายใน 2-3 สัปดาห์?',
                    'อาการร่วม: มีไข้ หรืออาการทางระบบอื่นๆ?'
                ],
                'non_drug_advice' => [
                    'หลีกเลี่ยงอาหารรสจัดหรืออาหารแข็งที่อาจระคายเคืองแผล',
                    'รักษาความสะอาดในช่องปากด้วยการแปรงฟันอย่างนุ่มนวล',
                    'พักผ่อนให้เพียงพอและจัดการความเครียด'
                ],
                'treatments' => [
                    'aphthous_ulcer' => [
                        ['name' => 'Triamcinolone acetonide in oral base', 'indication' => 'ยาสเตียรอยด์ ลดการอักเสบและอาการปวด'],
                        ['name' => 'Lidocaine gel', 'indication' => 'ยาชาเฉพาะที่ บรรเทาอาการปวดชั่วคราว']
                    ],
                    'cold_sore' => [
                        ['name' => 'Acyclovir cream', 'indication' => 'ใช้ทาตั้งแต่เริ่มมีอาการเพื่อลดความรุนแรงและระยะเวลาของโรค']
                    ]
                ],
                'referral_criteria' => [
                    'แผลมีขนาดใหญ่มาก หรือมีจำนวนมากผิดปกติ',
                    'แผลไม่หายภายใน 3 สัปดาห์',
                    'มีอาการเจ็บปวดรุนแรงจนรับประทานอาหารหรือดื่มน้ำไม่ได้',
                    'มีอาการทางระบบอื่นๆ ร่วมด้วย เช่น ไข้สูง หรือต่อมน้ำเหลืองโต'
                ]
            ]
        ];
        
        // ===== เพิ่มเติม: ท้องเสียในเด็ก =====
        $this->diseaseDatabase['pediatric'] = [
            'diarrhea_children' => [
                'name_th' => 'ท้องเสียในเด็ก',
                'name_en' => 'Diarrhea in Children',
                'keywords' => ['ท้องเสีย', 'ถ่ายเหลว', 'ลูกท้องเสีย', 'เด็กท้องเสีย', 'diarrhea', 'ท้องร่วง'],
                'assessment_questions' => [
                    'อาการร่วม: มีไข้สูง ถ่ายมีมูกเลือดปน หรือปวดท้องรุนแรง?',
                    'การรับประทาน: ยังพอทานอาหารหรือดื่มน้ำได้ไหม? มีการอาเจียนทุกครั้งที่ทาน?',
                    'อาการขาดน้ำ: ดูซึมลง ปากแห้ง หรือปัสสาวะน้อยกว่าปกติ?'
                ],
                'non_drug_advice' => [
                    'จิบสารละลายเกลือแร่ (ORS) บ่อยๆ เพื่อป้องกันการขาดน้ำ',
                    'หลีกเลี่ยงน้ำอัดลมหรือเครื่องดื่มเกลือแร่สำหรับนักกีฬา (น้ำตาลสูงเกินไป)',
                    'ทานอาหารอ่อน ย่อยง่าย เช่น ข้าวต้ม โจ๊ก',
                    'งดนมวัวชั่วคราว หรือใช้นมสูตร Lactose-free',
                    'ล้างมือให้สะอาดทุกครั้งก่อนเตรียมอาหารและหลังเข้าห้องน้ำ'
                ],
                'treatments' => [
                    'rehydration' => [
                        ['name' => 'ORS (Oral Rehydration Salt)', 'indication' => 'สำคัญที่สุด - ชดเชยน้ำและเกลือแร่']
                    ],
                    'supportive' => [
                        ['name' => 'Activated Charcoal', 'indication' => 'อาจช่วยดูดซับสารพิษ'],
                        ['name' => 'Zinc Supplement', 'indication' => 'อาจช่วยลดความรุนแรงและระยะเวลาของอาการท้องเสียในเด็ก']
                    ]
                ],
                'pharmacist_notes' => 'ในเด็กเล็ก ไม่แนะนำให้ใช้ยาหยุดถ่ายทันที เพราะร่างกายจำเป็นต้องขับเชื้อโรคออก',
                'referral_criteria' => [
                    'มีไข้สูง ถ่ายมีมูกเลือดปน หรือปวดท้องรุนแรง',
                    'อาเจียนทุกครั้งที่ทานจนไม่สามารถรับประทานอะไรได้',
                    'มีอาการขาดน้ำ (ปากแห้ง ซึม ปัสสาวะน้อย)',
                    'อาการไม่ดีขึ้นใน 24 ชั่วโมง',
                    'เด็กอายุน้อยกว่า 6 เดือน'
                ]
            ]
        ];
    }

    
    /**
     * Initialize Red Flags Database
     */
    private function initializeRedFlags(): void
    {
        $this->redFlags = [
            // ระบบผิวหนัง
            'skin' => [
                ['condition' => 'Anaphylaxis', 'keywords' => ['หายใจลำบาก', 'หน้าบวม', 'ปากบวม', 'ลิ้นบวม', 'ช็อก'], 'urgency' => 'emergency', 'message' => '⚠️ อาการแพ้รุนแรง (Anaphylaxis) - ต้องพบแพทย์ทันที!'],
                ['condition' => 'Severe infection', 'keywords' => ['ไข้สูง', 'หนอง', 'บวมแดงร้อน', 'ลุกลาม'], 'urgency' => 'urgent', 'message' => '⚠️ สงสัยการติดเชื้อรุนแรง - ควรพบแพทย์']
            ],
            
            // ระบบประสาท
            'neurological' => [
                ['condition' => 'Stroke', 'keywords' => ['แขนขาอ่อนแรง', 'พูดไม่ชัด', 'หน้าเบี้ยว', 'เห็นภาพซ้อน', 'ปวดหัวรุนแรงมาก'], 'urgency' => 'emergency', 'message' => '🚨 สัญญาณโรคหลอดเลือดสมอง - โทร 1669 ทันที!'],
                ['condition' => 'Thunderclap headache', 'keywords' => ['ปวดหัวรุนแรงเฉียบพลัน', 'ปวดหัวแบบไม่เคยเป็น'], 'urgency' => 'emergency', 'message' => '🚨 ปวดศีรษะรุนแรงเฉียบพลัน - ต้องพบแพทย์ทันที!'],
                ['condition' => 'Meningitis', 'keywords' => ['คอแข็ง', 'ไข้สูง', 'ปวดหัว', 'กลัวแสง'], 'urgency' => 'emergency', 'message' => '🚨 สงสัยเยื่อหุ้มสมองอักเสบ - ต้องพบแพทย์ทันที!']
            ],
            
            // ระบบทางเดินอาหาร
            'gastrointestinal' => [
                ['condition' => 'GI bleeding', 'keywords' => ['อาเจียนเป็นเลือด', 'ถ่ายดำ', 'ถ่ายเป็นเลือด'], 'urgency' => 'emergency', 'message' => '🚨 เลือดออกในทางเดินอาหาร - ต้องพบแพทย์ทันที!'],
                ['condition' => 'Severe dehydration', 'keywords' => ['ซึมมาก', 'ปัสสาวะน้อยมาก', 'ปากแห้งมาก', 'ตาลึกโบ๋'], 'urgency' => 'urgent', 'message' => '⚠️ ภาวะขาดน้ำรุนแรง - ควรพบแพทย์'],
                ['condition' => 'Appendicitis', 'keywords' => ['ปวดท้องรุนแรง', 'ปวดท้องน้อยขวา', 'ไข้', 'คลื่นไส้'], 'urgency' => 'urgent', 'message' => '⚠️ สงสัยไส้ติ่งอักเสบ - ควรพบแพทย์']
            ],
            
            // ระบบทางเดินหายใจ
            'respiratory' => [
                ['condition' => 'Respiratory distress', 'keywords' => ['หายใจลำบาก', 'หายใจหอบ', 'เหนื่อยมาก', 'ริมฝีปากเขียว'], 'urgency' => 'emergency', 'message' => '🚨 หายใจลำบาก - ต้องพบแพทย์ทันที!'],
                ['condition' => 'Hemoptysis', 'keywords' => ['ไอเป็นเลือด', 'ไอมีเลือดปน'], 'urgency' => 'urgent', 'message' => '⚠️ ไอเป็นเลือด - ควรพบแพทย์'],
                ['condition' => 'Pneumonia', 'keywords' => ['ไข้สูง', 'ไอ', 'เจ็บหน้าอก', 'หายใจเร็ว'], 'urgency' => 'urgent', 'message' => '⚠️ สงสัยปอดอักเสบ - ควรพบแพทย์']
            ],
            
            // หัวใจและหลอดเลือด
            'cardiovascular' => [
                ['condition' => 'Heart attack', 'keywords' => ['เจ็บหน้าอก', 'แน่นหน้าอก', 'ปวดร้าวไปแขน', 'เหงื่อออก', 'คลื่นไส้'], 'urgency' => 'emergency', 'message' => '🚨 สัญญาณหัวใจวาย - โทร 1669 ทันที!']
            ],
            
            // เด็ก
            'pediatric' => [
                ['condition' => 'High fever in infant', 'keywords' => ['ทารก', 'ไข้สูง', 'เด็กเล็ก', 'ไข้'], 'urgency' => 'urgent', 'message' => '⚠️ ไข้สูงในเด็กเล็ก - ควรพบแพทย์'],
                ['condition' => 'Severe dehydration in child', 'keywords' => ['เด็ก', 'ซึม', 'ไม่ยอมกิน', 'ปัสสาวะน้อย'], 'urgency' => 'urgent', 'message' => '⚠️ เด็กมีอาการขาดน้ำ - ควรพบแพทย์']
            ],
            
            // สตรี
            'womens_health' => [
                ['condition' => 'Ectopic pregnancy', 'keywords' => ['ปวดท้องน้อย', 'เลือดออก', 'ตั้งครรภ์', 'ประจำเดือนขาด'], 'urgency' => 'emergency', 'message' => '🚨 สงสัยท้องนอกมดลูก - ต้องพบแพทย์ทันที!']
            ]
        ];
    }
    
    /**
     * Initialize Corticosteroid Potency Chart (7 Levels)
     */
    private function initializeCorticosteroidPotency(): void
    {
        $this->corticosteroidPotency = [
            1 => [ // แรงสุด
                ['name' => 'Clobetasol propionate', 'strength' => '0.05%', 'forms' => 'Cream, Ointment, Shampoo'],
                ['name' => 'Betamethasone dipropionate', 'strength' => '0.05%', 'forms' => 'Ointment, Gel']
            ],
            2 => [
                ['name' => 'Desoximetasone', 'strength' => '0.25%', 'forms' => 'Cream, Ointment'],
                ['name' => 'Fluticasone propionate', 'strength' => '0.005%', 'forms' => 'Ointment'],
                ['name' => 'Mometasone furoate', 'strength' => '0.1%', 'forms' => 'Ointment']
            ],
            3 => [
                ['name' => 'Betamethasone dipropionate', 'strength' => '0.05%', 'forms' => 'Cream'],
                ['name' => 'Fluticasone propionate', 'strength' => '0.05%', 'forms' => 'Cream']
            ],
            4 => [
                ['name' => 'Mometasone furoate', 'strength' => '0.1%', 'forms' => 'Cream, Lotion, Solution'],
                ['name' => 'Triamcinolone acetonide', 'strength' => '0.1%', 'forms' => 'Cream, Ointment']
            ],
            5 => [
                ['name' => 'Betamethasone valerate', 'strength' => '0.1%', 'forms' => 'Cream, Lotion'],
                ['name' => 'Fluticasone propionate', 'strength' => '0.05%', 'forms' => 'Lotion']
            ],
            6 => [
                ['name' => 'Desonide', 'strength' => '0.05%', 'forms' => 'Cream, Ointment, Gel'],
                ['name' => 'Hydrocortisone butyrate', 'strength' => '0.1%', 'forms' => 'Cream']
            ],
            7 => [ // แรงอ่อนสุด
                ['name' => 'Hydrocortisone', 'strength' => '1%', 'forms' => 'Cream, Ointment, Lotion, Spray'],
                ['name' => 'Hydrocortisone acetate', 'strength' => '1%', 'forms' => 'Cream']
            ]
        ];
    }
    
    /**
     * ดึงข้อมูลทั้งหมดสำหรับ category
     */
    public function getCategory(string $category): array
    {
        return $this->diseaseDatabase[$category] ?? [];
    }
    
    /**
     * ดึงรายชื่อ categories ทั้งหมด
     */
    public function getCategories(): array
    {
        return array_keys($this->diseaseDatabase);
    }
    
    /**
     * Format ข้อมูลโรคเป็น context สำหรับ AI
     */
    public function formatDiseaseForAI(array $disease): string
    {
        $text = "## {$disease['name_th']} ({$disease['name_en']})\n\n";
        
        // คำถามประเมิน
        if (!empty($disease['assessment_questions'])) {
            $text .= "### คำถามประเมินอาการ:\n";
            foreach ($disease['assessment_questions'] as $q) {
                $text .= "- {$q}\n";
            }
            $text .= "\n";
        }
        
        // คำแนะนำที่ไม่ใช้ยา
        if (!empty($disease['non_drug_advice'])) {
            $text .= "### คำแนะนำการปฏิบัติตัว:\n";
            foreach ($disease['non_drug_advice'] as $advice) {
                $text .= "- {$advice}\n";
            }
            $text .= "\n";
        }
        
        // การรักษา
        if (!empty($disease['treatments'])) {
            $text .= "### ทางเลือกในการรักษา:\n";
            foreach ($disease['treatments'] as $type => $drugs) {
                $text .= "**{$type}:**\n";
                foreach ($drugs as $drug) {
                    $text .= "- {$drug['name']}";
                    if (!empty($drug['dose'])) $text .= " ({$drug['dose']})";
                    if (!empty($drug['indication'])) $text .= " - {$drug['indication']}";
                    $text .= "\n";
                }
            }
            $text .= "\n";
        }
        
        // หมายเหตุสำหรับเภสัชกร
        if (!empty($disease['pharmacist_notes'])) {
            $text .= "### หมายเหตุสำหรับเภสัชกร:\n{$disease['pharmacist_notes']}\n\n";
        }
        
        // เกณฑ์ส่งต่อแพทย์
        if (!empty($disease['referral_criteria'])) {
            $text .= "### เกณฑ์การส่งต่อแพทย์:\n";
            foreach ($disease['referral_criteria'] as $criteria) {
                $text .= "- {$criteria}\n";
            }
        }
        
        return $text;
    }
    
    /**
     * สร้าง System Prompt สำหรับ MIMS AI Pharmacist
     */
    public function buildMIMSSystemPrompt(): string
    {
        return <<<PROMPT
คุณคือ "MIMS Online Pharmacist AI" ผู้ช่วยเภสัชกรอัจฉริยะที่ให้คำปรึกษาด้านสุขภาพเบื้องต้น โดยอ้างอิงข้อมูลจาก MIMS Pharmacy Thailand 2023 อย่างเคร่งครัด

## วัตถุประสงค์:
ให้คำปรึกษาเภสัชกรรมทางไกล (Tele-pharmacy) โดยเน้นความปลอดภัยสูงสุด การคัดกรองผู้ป่วย และการใช้ยาอย่างสมเหตุสมผล

## ข้อจำกัดและกฎเหล็ก:

1. **Reference Only**: ตอบคำถามโดยใช้ข้อมูลจาก MIMS Knowledge Base เท่านั้น ห้ามคิดค้นยาหรือโดสยาเอง หากไม่มีข้อมูล ให้ตอบว่า "ไม่มีข้อมูลในฐานข้อมูล MIMS ปัจจุบัน โปรดปรึกษาแพทย์หรือเภสัชกรใกล้บ้าน"

2. **Safety First (Red Flag Check)**: ก่อนแนะนำยา ต้องตรวจสอบ "อาการที่ต้องส่งต่อแพทย์" (Referral criteria) เสมอ หากพบอาการอันตราย ให้แนะนำให้ไปโรงพยาบาลทันทีและหยุดการให้คำแนะนำยา

3. **Step-by-Step Logic**: ห้ามให้ชื่อยาทันที ต้องทำตามขั้นตอน:
   - ซักอาการ (Symptom Triage)
   - ตรวจสอบข้อห้าม (Red Flag Check)
   - แนะนำการปฏิบัติตัว (Non-drug advice)
   - แนะนำยา (ถ้าจำเป็น)

4. **Always Ask for Specific Patient Groups**: ต้องถามเสมอว่าผู้ป่วยเป็น:
   - เด็ก (อายุเท่าไหร่?)
   - หญิงตั้งครรภ์/ให้นมบุตร
   - ผู้สูงอายุ
   - มีโรคประจำตัว/แพ้ยา

## ขั้นตอนการทำงาน:

### Step 1: Symptom Triage (การคัดกรองอาการ)
- เมื่อผู้ใช้แจ้งอาการ ให้ค้นหาหัวข้อโรคที่เกี่ยวข้องใน MIMS Knowledge Base
- สร้างคำถาม 1-3 ข้อ เพื่อแยกโรคอันตราย

### Step 2: Analysis & Recommendation (การวิเคราะห์และแนะนำ)
- **กรณีส่งต่อแพทย์**: หากคำตอบเข้าข่ายอันตราย → แจ้งเตือนสีแดง "กรุณาพบแพทย์ทันทีเนื่องจาก..."
- **กรณีดูแลเองได้**:
  1. Non-Drug Advice: ให้คำแนะนำการปฏิบัติตัวก่อนเสมอ
  2. Pharmacotherapy: แนะนำยากลุ่มแรก (First-line drug) ตามตาราง MIMS
  3. Dosage: ระบุขนาดและวิธีการใช้ยา (แยกเด็ก/ผู้ใหญ่ให้ชัดเจน)

### Step 3: Counseling (เภสัชกรชวนคุย)
- เตือนเรื่องผลข้างเคียงที่สำคัญ (Side effects)
- ถามย้ำเรื่องประวัติแพ้ยา การตั้งครรภ์ หรือโรคประจำตัวก่อนสรุปจบ

## รูปแบบการตอบ:
- มืออาชีพ เป็นกันเอง เข้าใจง่าย
- ใช้ภาษาไทยที่ถูกต้อง ไม่วิชาการจนเกินไป
- ใช้ emoji บ้างให้ดูเป็นมิตร 😊💊🩺
- ห้ามใช้ bullet points (*), ตัวเลข (1. 2.), หรือ **ตัวหนา** ในการตอบ
- ตอบเป็นประโยคธรรมชาติ

## 📌 การอ้างอิงแหล่งข้อมูล (Source Citation) - สำคัญมาก!
ทุกคำตอบต้องระบุแหล่งที่มาของข้อมูลอย่างชัดเจน โดยใช้รูปแบบดังนี้:

1. **ข้อมูลทางการแพทย์/ยา** (จาก MIMS Knowledge Base):
   📚 [MIMS 2023: ชื่อโรค/หัวข้อ]
   ตัวอย่าง: 📚 [MIMS 2023: Diarrhea in Children]
   ตัวอย่าง: 📚 [MIMS 2023: Allergic Rhinitis]
   ตัวอย่าง: 📚 [MIMS 2023: Topical Corticosteroids Potency Chart]

2. **ข้อมูลสินค้า/ยาในร้าน** (จากระบบสินค้าร้านยา):
   🛒 [สินค้าในร้าน]
   ใช้เมื่อแนะนำสินค้าที่มีขายในร้าน พร้อมราคา

3. **ข้อมูลผู้ป่วย** (จากประวัติในระบบ):
   👤 [ข้อมูลผู้ป่วย]
   ใช้เมื่ออ้างอิงประวัติแพ้ยา โรคประจำตัว

4. **คำแนะนำทั่วไป** (ความรู้พื้นฐานทางเภสัชกรรม):
   💡 [คำแนะนำทั่วไป]
   ใช้สำหรับคำแนะนำการปฏิบัติตัวทั่วไป

วางแหล่งอ้างอิงไว้ท้ายย่อหน้าหรือท้ายคำตอบ เพื่อให้ผู้ใช้ทราบว่าข้อมูลมาจากไหน

## ตัวอย่างการตอบ:
ผู้ใช้: "ลูกชาย 5 ขวบ ท้องเสียมาตั้งแต่เช้า ถ่ายเหลว 3 รอบแล้ว"

AI: "สวัสดีค่ะ เข้าใจความเป็นห่วงคุณพ่อนะคะ สำหรับอาการท้องเสียในเด็กเล็ก (อายุ 5 ขวบ) เราต้องระวังเรื่องการขาดน้ำเป็นพิเศษค่ะ 😊

ขออนุญาตสอบถามเพิ่มเติมเพื่อประเมินความรุนแรงก่อนนะคะ

น้องมีอาการไข้สูง ถ่ายมีมูกเลือดปน หรือปวดท้องรุนแรงร่วมด้วยไหมคะ?

น้องยังพอทานอาหารหรือดื่มน้ำได้ไหม หรือมีการอาเจียนทุกครั้งที่ทานคะ?

น้องดูซึมลง ปากแห้ง หรือปัสสาวะน้อยกว่าปกติไหมคะ?

📚 [MIMS 2023: Acute Diarrhea in Children]"

---

ตัวอย่างการแนะนำยาพร้อมอ้างอิง:

AI: "จากอาการที่เล่ามา น้องน่าจะเป็นท้องเสียจากการติดเชื้อไวรัสค่ะ สิ่งสำคัญที่สุดคือการป้องกันภาวะขาดน้ำนะคะ 💡 [คำแนะนำทั่วไป]

แนะนำให้ดื่มสารละลายเกลือแร่ ORS ทดแทนน้ำที่เสียไป โดยจิบทีละน้อยบ่อยๆ ค่ะ 📚 [MIMS 2023: Oral Rehydration Therapy]

ทางร้านมี ORS ผงเกลือแร่ ราคา 15 บาท/ซอง มีสต็อกพร้อมจำหน่ายค่ะ 🛒 [สินค้าในร้าน]"
PROMPT;
    }
}
