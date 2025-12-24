<?php
/**
 * PharmacyFlexTemplates - Flex Message Templates สำหรับร้านยา
 * Version 2.0 - Professional Pharmacy LINE Flex Messages
 */

namespace Modules\AIChat\Templates;

class PharmacyFlexTemplates
{
    /**
     * สร้าง Flex สรุปการซักประวัติ
     */
    public static function triageSummary(array $data): array
    {
        $symptoms = implode(', ', $data['symptoms'] ?? ['ไม่ระบุ']);
        $duration = $data['duration'] ?? 'ไม่ระบุ';
        $severity = $data['severity'] ?? 5;
        $severityColor = $severity >= 7 ? '#EF4444' : ($severity >= 4 ? '#F59E0B' : '#10B981');
        
        return [
            'type' => 'flex',
            'altText' => 'สรุปการประเมินอาการ',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => '📋 สรุปการประเมินอาการ',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'color' => '#FFFFFF',
                        ],
                    ],
                    'backgroundColor' => '#10B981',
                    'paddingAll' => '15px',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => '🩺 อาการ', 'size' => 'sm', 'color' => '#666666', 'flex' => 2],
                                ['type' => 'text', 'text' => $symptoms, 'size' => 'sm', 'color' => '#333333', 'flex' => 4, 'wrap' => true],
                            ],
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => '⏱️ ระยะเวลา', 'size' => 'sm', 'color' => '#666666', 'flex' => 2],
                                ['type' => 'text', 'text' => $duration, 'size' => 'sm', 'color' => '#333333', 'flex' => 4],
                            ],
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                ['type' => 'text', 'text' => '📊 ความรุนแรง', 'size' => 'sm', 'color' => '#666666', 'flex' => 2],
                                ['type' => 'text', 'text' => "{$severity}/10", 'size' => 'sm', 'color' => $severityColor, 'weight' => 'bold', 'flex' => 4],
                            ],
                            'margin' => 'md',
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'text',
                            'text' => '⚕️ กรุณารอเภสัชกรตรวจสอบ',
                            'size' => 'xs',
                            'color' => '#888888',
                            'margin' => 'lg',
                            'align' => 'center',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex แนะนำยา
     */
    public static function drugRecommendation(array $drugs, array $warnings = []): array
    {
        $contents = [];
        
        // Header
        $contents[] = [
            'type' => 'text',
            'text' => '💊 ยาที่แนะนำ',
            'weight' => 'bold',
            'size' => 'lg',
            'color' => '#333333',
        ];
        
        $contents[] = ['type' => 'separator', 'margin' => 'md'];
        
        // Drug list
        foreach (array_slice($drugs, 0, 3) as $drug) {
            $contents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => $drug['name'], 'weight' => 'bold', 'size' => 'sm'],
                            ['type' => 'text', 'text' => $drug['usage_instructions'] ?? 'ตามฉลากยา', 'size' => 'xs', 'color' => '#666666', 'wrap' => true],
                        ],
                        'flex' => 4,
                    ],
                    [
                        'type' => 'text',
                        'text' => '฿' . number_format($drug['price']),
                        'size' => 'sm',
                        'color' => '#10B981',
                        'weight' => 'bold',
                        'align' => 'end',
                        'flex' => 1,
                    ],
                ],
                'margin' => 'lg',
                'paddingAll' => '10px',
                'backgroundColor' => '#F9FAFB',
                'cornerRadius' => '8px',
            ];
        }
        
        // Warnings
        if (!empty($warnings)) {
            $contents[] = ['type' => 'separator', 'margin' => 'lg'];
            $contents[] = [
                'type' => 'text',
                'text' => '⚠️ ข้อควรระวัง',
                'weight' => 'bold',
                'size' => 'sm',
                'color' => '#EF4444',
                'margin' => 'md',
            ];
            
            foreach (array_slice($warnings, 0, 2) as $warning) {
                $contents[] = [
                    'type' => 'text',
                    'text' => '• ' . $warning,
                    'size' => 'xs',
                    'color' => '#666666',
                    'wrap' => true,
                    'margin' => 'sm',
                ];
            }
        }
        
        return [
            'type' => 'flex',
            'altText' => 'ยาที่แนะนำ',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $contents,
                    'paddingAll' => '15px',
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '🛒 สั่งซื้อยา', 'text' => 'สั่งซื้อ'],
                            'style' => 'primary',
                            'color' => '#10B981',
                        ],
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '📞 ปรึกษาเภสัชกร', 'text' => 'ปรึกษาเภสัชกร'],
                            'style' => 'secondary',
                            'margin' => 'sm',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }
    
    /**
     * สร้าง Flex แจ้งเตือน Red Flag
     */
    public static function redFlagAlert(array $redFlags): array
    {
        $flagContents = [];
        
        foreach ($redFlags as $flag) {
            $flagContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️', 'size' => 'lg', 'flex' => 0],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            ['type' => 'text', 'text' => $flag['message'], 'size' => 'sm', 'weight' => 'bold', 'wrap' => true],
                            ['type' => 'text', 'text' => $flag['action'] ?? '', 'size' => 'xs', 'color' => '#666666', 'wrap' => true, 'margin' => 'sm'],
                        ],
                        'flex' => 1,
                        'margin' => 'md',
                    ],
                ],
                'margin' => 'md',
                'paddingAll' => '10px',
                'backgroundColor' => '#FEF2F2',
                'cornerRadius' => '8px',
            ];
        }
        
        return [
            'type' => 'flex',
            'altText' => '🚨 พบอาการที่ต้องระวัง',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '🚨 พบอาการที่ต้องระวัง', 'weight' => 'bold', 'size' => 'lg', 'color' => '#FFFFFF'],
                    ],
                    'backgroundColor' => '#EF4444',
                    'paddingAll' => '15px',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $flagContents,
                    'paddingAll' => '15px',
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => ['type' => 'uri', 'label' => '📞 โทร 1669', 'uri' => 'tel:1669'],
                            'style' => 'primary',
                            'color' => '#EF4444',
                        ],
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '📹 Video Call เภสัชกร', 'text' => 'video call'],
                            'style' => 'secondary',
                            'margin' => 'sm',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }

    /**
     * สร้าง Flex ข้อมูลยา
     */
    public static function drugInfo(array $drug): array
    {
        $contents = [
            [
                'type' => 'text',
                'text' => $drug['name'],
                'weight' => 'bold',
                'size' => 'xl',
                'color' => '#333333',
            ],
        ];
        
        if (!empty($drug['generic_name'])) {
            $contents[] = [
                'type' => 'text',
                'text' => "({$drug['generic_name']})",
                'size' => 'sm',
                'color' => '#888888',
            ];
        }
        
        $contents[] = ['type' => 'separator', 'margin' => 'lg'];
        
        // Price
        $contents[] = [
            'type' => 'box',
            'layout' => 'horizontal',
            'contents' => [
                ['type' => 'text', 'text' => '💰 ราคา', 'size' => 'sm', 'color' => '#666666', 'flex' => 2],
                ['type' => 'text', 'text' => '฿' . number_format($drug['price']), 'size' => 'lg', 'color' => '#10B981', 'weight' => 'bold', 'flex' => 3],
            ],
            'margin' => 'lg',
        ];
        
        // Usage
        if (!empty($drug['usage_instructions'])) {
            $contents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '📝 วิธีใช้', 'size' => 'sm', 'color' => '#666666'],
                    ['type' => 'text', 'text' => $drug['usage_instructions'], 'size' => 'sm', 'wrap' => true, 'margin' => 'sm'],
                ],
                'margin' => 'lg',
            ];
        }
        
        // Warnings
        if (!empty($drug['warnings'])) {
            $contents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '⚠️ คำเตือน', 'size' => 'sm', 'color' => '#EF4444'],
                    ['type' => 'text', 'text' => $drug['warnings'], 'size' => 'xs', 'color' => '#666666', 'wrap' => true, 'margin' => 'sm'],
                ],
                'margin' => 'lg',
                'paddingAll' => '10px',
                'backgroundColor' => '#FEF2F2',
                'cornerRadius' => '8px',
            ];
        }
        
        return [
            'type' => 'flex',
            'altText' => "ข้อมูลยา: {$drug['name']}",
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'hero' => !empty($drug['image_url']) ? [
                    'type' => 'image',
                    'url' => $drug['image_url'],
                    'size' => 'full',
                    'aspectRatio' => '20:13',
                    'aspectMode' => 'cover',
                ] : null,
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $contents,
                    'paddingAll' => '15px',
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'horizontal',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '🛒 เพิ่มลงตะกร้า', 'text' => "สั่งซื้อ {$drug['name']}"],
                            'style' => 'primary',
                            'color' => '#10B981',
                            'flex' => 2,
                        ],
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '❓ ถาม', 'text' => "สอบถามเกี่ยวกับ {$drug['name']}"],
                            'style' => 'secondary',
                            'flex' => 1,
                            'margin' => 'sm',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }
    
    /**
     * สร้าง Flex เมนูหลัก Pharmacy
     */
    public static function pharmacyMenu(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'เมนูร้านยา',
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '🏥 ร้านยาออนไลน์', 'weight' => 'bold', 'size' => 'xl', 'color' => '#FFFFFF'],
                        ['type' => 'text', 'text' => 'พร้อมให้บริการ 24 ชั่วโมง', 'size' => 'sm', 'color' => '#FFFFFF', 'margin' => 'sm'],
                    ],
                    'backgroundColor' => '#10B981',
                    'paddingAll' => '20px',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                self::menuButton('🩺', 'ประเมินอาการ', 'ประเมินอาการ'),
                                self::menuButton('💊', 'ค้นหายา', 'ค้นหายา'),
                            ],
                            'spacing' => 'md',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                self::menuButton('📞', 'ปรึกษาเภสัชกร', 'ปรึกษาเภสัชกร'),
                                self::menuButton('📹', 'Video Call', 'video call'),
                            ],
                            'spacing' => 'md',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'contents' => [
                                self::menuButton('🛒', 'ตะกร้าสินค้า', 'ตะกร้า'),
                                self::menuButton('📋', 'ประวัติการสั่ง', 'ประวัติ'),
                            ],
                            'spacing' => 'md',
                            'margin' => 'md',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }
    
    private static function menuButton(string $icon, string $label, string $action): array
    {
        return [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
                ['type' => 'text', 'text' => $icon, 'size' => 'xxl', 'align' => 'center'],
                ['type' => 'text', 'text' => $label, 'size' => 'sm', 'align' => 'center', 'margin' => 'sm'],
            ],
            'flex' => 1,
            'paddingAll' => '15px',
            'backgroundColor' => '#F3F4F6',
            'cornerRadius' => '12px',
            'action' => ['type' => 'message', 'text' => $action],
        ];
    }
    
    /**
     * สร้าง Flex รอเภสัชกรยืนยัน
     */
    public static function waitingForPharmacist(): array
    {
        return [
            'type' => 'flex',
            'altText' => 'รอเภสัชกรตรวจสอบ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => '⏳', 'size' => '3xl', 'align' => 'center'],
                        ['type' => 'text', 'text' => 'รอเภสัชกรตรวจสอบ', 'weight' => 'bold', 'size' => 'lg', 'align' => 'center', 'margin' => 'lg'],
                        ['type' => 'text', 'text' => 'เภสัชกรจะตรวจสอบและติดต่อกลับ\nภายใน 5-10 นาทีค่ะ', 'size' => 'sm', 'color' => '#666666', 'align' => 'center', 'wrap' => true, 'margin' => 'md'],
                        ['type' => 'separator', 'margin' => 'xl'],
                        ['type' => 'text', 'text' => '💡 ระหว่างรอ สามารถเตรียมข้อมูลเพิ่มเติม\nหรือถ่ายรูปยาที่ทานอยู่ได้ค่ะ', 'size' => 'xs', 'color' => '#888888', 'wrap' => true, 'margin' => 'lg', 'align' => 'center'],
                    ],
                    'paddingAll' => '20px',
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => ['type' => 'message', 'label' => '📷 ส่งรูปยา', 'text' => 'ส่งรูปยา'],
                            'style' => 'secondary',
                        ],
                    ],
                    'paddingAll' => '15px',
                ],
            ],
        ];
    }
}
