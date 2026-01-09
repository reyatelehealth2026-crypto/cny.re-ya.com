<?php
/**
 * Insert Sample Health Articles
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Insert Sample Health Articles</h2>";
echo "<pre>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES LIKE 'health_articles'");
    if (!$stmt->fetch()) {
        echo "❌ Table health_articles not found. Please run migration first:\n";
        echo "   /install/run_health_articles_migration.php\n";
        exit;
    }
    
    // Article 1: วิธีเลือกซื้อยาสามัญประจำบ้าน
    $article1 = [
        'title' => 'วิธีเลือกซื้อยาสามัญประจำบ้านอย่างถูกต้อง',
        'slug' => 'how-to-buy-home-medicine-' . time(),
        'excerpt' => 'แนะนำวิธีเลือกซื้อยาสามัญประจำบ้านที่ควรมีติดบ้านไว้ พร้อมข้อควรระวังในการใช้ยาอย่างปลอดภัย',
        'content' => '<h2>ยาสามัญประจำบ้านที่ควรมี</h2>
<p>ยาสามัญประจำบ้านเป็นยาที่ใช้รักษาอาการเจ็บป่วยเบื้องต้น ที่ทุกบ้านควรมีติดไว้เพื่อความสะดวกในการดูแลสุขภาพเบื้องต้น</p>

<h3>1. ยาแก้ปวดลดไข้</h3>
<p><strong>พาราเซตามอล (Paracetamol)</strong> เป็นยาแก้ปวดและลดไข้ที่ปลอดภัย เหมาะสำหรับทุกวัย</p>
<ul>
<li>ผู้ใหญ่: รับประทานครั้งละ 1-2 เม็ด (500-1000 มก.) ทุก 4-6 ชั่วโมง</li>
<li>เด็ก: ใช้ตามน้ำหนักตัว ควรปรึกษาเภสัชกร</li>
<li>ไม่ควรรับประทานเกิน 8 เม็ดต่อวัน</li>
</ul>

<h3>2. ยาแก้แพ้ ลดน้ำมูก</h3>
<p><strong>คลอร์เฟนิรามีน (Chlorpheniramine)</strong> หรือ <strong>ลอราทาดีน (Loratadine)</strong></p>
<ul>
<li>ใช้บรรเทาอาการแพ้ คัดจมูก น้ำมูกไหล</li>
<li>คลอร์เฟนิรามีนอาจทำให้ง่วงนอน</li>
<li>ลอราทาดีนไม่ทำให้ง่วง เหมาะกับผู้ที่ต้องขับรถ</li>
</ul>

<h3>3. ยาแก้ท้องเสีย</h3>
<p><strong>ผงเกลือแร่ ORS</strong> สำหรับทดแทนน้ำและเกลือแร่ที่สูญเสียไป</p>
<ul>
<li>ละลายในน้ำสะอาด 1 ซอง ต่อน้ำ 200 มล.</li>
<li>จิบทีละน้อยบ่อยๆ</li>
<li>หากท้องเสียรุนแรงหรือมีไข้สูง ควรพบแพทย์</li>
</ul>

<h3>4. ยาทาแผล</h3>
<ul>
<li><strong>โพวิโดน-ไอโอดีน (Povidone-Iodine)</strong> - ฆ่าเชื้อแผลสด</li>
<li><strong>ยาใส่แผล (Antiseptic cream)</strong> - ป้องกันการติดเชื้อ</li>
<li><strong>พลาสเตอร์ปิดแผล</strong> - ปกป้องแผลจากสิ่งสกปรก</li>
</ul>

<h3>ข้อควรระวังในการใช้ยา</h3>
<ol>
<li>อ่านฉลากยาทุกครั้งก่อนใช้</li>
<li>ตรวจสอบวันหมดอายุ</li>
<li>เก็บยาในที่แห้ง พ้นแสงแดด</li>
<li>หากอาการไม่ดีขึ้นใน 2-3 วัน ควรพบแพทย์</li>
<li>ปรึกษาเภสัชกรก่อนใช้ยาหากมีโรคประจำตัว</li>
</ol>

<p><em>หากมีข้อสงสัยเกี่ยวกับการใช้ยา สามารถปรึกษาเภสัชกรของเราได้ตลอดเวลา</em></p>',
        'featured_image' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800',
        'author_name' => 'ภก.สมชาย รักสุขภาพ',
        'author_title' => 'เภสัชกรชุมชน',
        'tags' => '["ยาสามัญประจำบ้าน", "การใช้ยา", "สุขภาพ", "เภสัชกร"]',
        'meta_title' => 'วิธีเลือกซื้อยาสามัญประจำบ้าน - คู่มือฉบับสมบูรณ์',
        'meta_description' => 'แนะนำยาสามัญประจำบ้านที่ควรมี พร้อมวิธีใช้และข้อควรระวัง โดยเภสัชกรผู้เชี่ยวชาญ',
        'is_featured' => 1,
        'is_published' => 1
    ];
    
    // Article 2: วิตามินและอาหารเสริมที่ควรรู้
    $article2 = [
        'title' => 'วิตามินและอาหารเสริม ทานอย่างไรให้ได้ประโยชน์สูงสุด',
        'slug' => 'vitamins-supplements-guide-' . time(),
        'excerpt' => 'ทำความเข้าใจเรื่องวิตามินและอาหารเสริม ควรทานตอนไหน ทานอย่างไร และใครควรทานบ้าง',
        'content' => '<h2>ทำความรู้จักวิตามินและอาหารเสริม</h2>
<p>วิตามินและอาหารเสริมเป็นสิ่งที่หลายคนสนใจ แต่การทานให้ถูกวิธีจะช่วยให้ได้ประโยชน์สูงสุด</p>

<h3>วิตามินที่ละลายในน้ำ vs ละลายในไขมัน</h3>

<h4>วิตามินละลายในน้ำ</h4>
<p>ได้แก่ วิตามิน B และ C</p>
<ul>
<li>ร่างกายไม่สะสม ต้องได้รับทุกวัน</li>
<li>ทานตอนไหนก็ได้</li>
<li>ส่วนเกินจะถูกขับออกทางปัสสาวะ</li>
</ul>

<h4>วิตามินละลายในไขมัน</h4>
<p>ได้แก่ วิตามิน A, D, E, K</p>
<ul>
<li>ควรทานพร้อมอาหารที่มีไขมัน</li>
<li>ร่างกายสะสมได้ ไม่ควรทานเกินขนาด</li>
<li>ทานมากเกินไปอาจเป็นพิษได้</li>
</ul>

<h3>วิตามินยอดนิยมและประโยชน์</h3>

<h4>🍊 วิตามินซี (Vitamin C)</h4>
<ul>
<li>เสริมภูมิคุ้มกัน ต้านหวัด</li>
<li>ช่วยสร้างคอลลาเจน บำรุงผิว</li>
<li>ปริมาณแนะนำ: 60-90 มก./วัน</li>
<li>ทานได้ทุกเวลา แต่ไม่ควรทานตอนท้องว่างหากกระเพาะอ่อนแอ</li>
</ul>

<h4>☀️ วิตามินดี (Vitamin D)</h4>
<ul>
<li>ช่วยดูดซึมแคลเซียม บำรุงกระดูก</li>
<li>เสริมภูมิคุ้มกัน</li>
<li>ควรทานพร้อมอาหารมื้อที่มีไขมัน</li>
<li>คนไทยส่วนใหญ่ขาดวิตามินดี</li>
</ul>

<h4>💪 วิตามินบีรวม (Vitamin B Complex)</h4>
<ul>
<li>ช่วยเรื่องพลังงาน ลดความเหนื่อยล้า</li>
<li>บำรุงระบบประสาท</li>
<li>ควรทานตอนเช้าหรือกลางวัน (อาจทำให้นอนไม่หลับ)</li>
</ul>

<h4>🐟 โอเมก้า 3 (Omega-3)</h4>
<ul>
<li>บำรุงสมอง หัวใจ</li>
<li>ลดการอักเสบ</li>
<li>ควรทานพร้อมอาหาร</li>
<li>เลือกแบบที่ผ่านการกลั่นเพื่อลดกลิ่นคาว</li>
</ul>

<h3>ใครควรทานอาหารเสริม?</h3>
<ul>
<li>ผู้ที่ทานอาหารไม่ครบ 5 หมู่</li>
<li>ผู้สูงอายุ</li>
<li>หญิงตั้งครรภ์ (ควรปรึกษาแพทย์)</li>
<li>ผู้ที่ออกกำลังกายหนัก</li>
<li>ผู้ที่มีโรคประจำตัวบางชนิด</li>
</ul>

<h3>⚠️ ข้อควรระวัง</h3>
<ol>
<li>ไม่ควรทานเกินขนาดที่แนะนำ</li>
<li>แจ้งแพทย์/เภสัชกรหากทานยาอื่นอยู่</li>
<li>เลือกซื้อจากแหล่งที่น่าเชื่อถือ</li>
<li>ตรวจสอบเลข อย. ก่อนซื้อ</li>
</ol>

<p><strong>สนใจปรึกษาเรื่องวิตามินและอาหารเสริม?</strong> ติดต่อเภสัชกรของเราได้เลยครับ</p>',
        'featured_image' => 'https://images.unsplash.com/photo-1550572017-edd951aa8f72?w=800',
        'author_name' => 'ภญ.สมหญิง ใจดี',
        'author_title' => 'เภสัชกร',
        'tags' => '["วิตามิน", "อาหารเสริม", "สุขภาพ", "โภชนาการ"]',
        'meta_title' => 'วิตามินและอาหารเสริม ทานอย่างไรให้ได้ประโยชน์',
        'meta_description' => 'คู่มือการทานวิตามินและอาหารเสริมอย่างถูกวิธี ควรทานตอนไหน ทานเท่าไหร่ โดยเภสัชกร',
        'is_featured' => 1,
        'is_published' => 1
    ];
    
    // Insert articles
    $sql = "INSERT INTO health_articles 
            (title, slug, excerpt, content, featured_image, author_name, author_title, tags, meta_title, meta_description, is_featured, is_published, published_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $db->prepare($sql);
    
    // Insert Article 1
    $stmt->execute([
        $article1['title'],
        $article1['slug'],
        $article1['excerpt'],
        $article1['content'],
        $article1['featured_image'],
        $article1['author_name'],
        $article1['author_title'],
        $article1['tags'],
        $article1['meta_title'],
        $article1['meta_description'],
        $article1['is_featured'],
        $article1['is_published']
    ]);
    echo "✅ Created article: {$article1['title']}\n";
    
    // Insert Article 2
    $stmt->execute([
        $article2['title'],
        $article2['slug'],
        $article2['excerpt'],
        $article2['content'],
        $article2['featured_image'],
        $article2['author_name'],
        $article2['author_title'],
        $article2['tags'],
        $article2['meta_title'],
        $article2['meta_description'],
        $article2['is_featured'],
        $article2['is_published']
    ]);
    echo "✅ Created article: {$article2['title']}\n";
    
    echo "\n✅ Sample articles created successfully!\n";
    echo "\nView articles at:\n";
    echo "- " . BASE_URL . "articles.php\n";
    echo "- " . BASE_URL . "index.php (scroll to บทความสุขภาพ section)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='../articles.php'>← View Articles</a> | <a href='../admin/landing-settings.php?tab=articles'>Manage Articles</a></p>";
