# PWA Icons

## วิธีสร้าง Icons

### ตัวเลือกที่ 1: ใช้ Online Generator (แนะนำ)

1. ไปที่ https://www.pwabuilder.com/imageGenerator
2. อัพโหลดไฟล์ `icon.svg` หรือรูปภาพขนาด 512x512
3. ดาวน์โหลด icons ทั้งหมด
4. วางไฟล์ในโฟลเดอร์นี้

### ตัวเลือกที่ 2: ใช้ ImageMagick

```bash
# ติดตั้ง ImageMagick
# macOS: brew install imagemagick
# Ubuntu: sudo apt install imagemagick

# สร้าง icons
for size in 16 32 72 96 128 144 152 192 384 512; do
  convert icon.svg -resize ${size}x${size} icon-${size}x${size}.png
done
```

### ตัวเลือกที่ 3: ใช้ PHP Script

```bash
php generate_icons.php
```

## ไฟล์ที่ต้องมี

- icon-16x16.png
- icon-32x32.png
- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png

## Splash Screens (Optional)

สำหรับ iOS ควรมี splash screens ในโฟลเดอร์ `../splash/`:
- splash-640x1136.png (iPhone 5)
- splash-750x1334.png (iPhone 6/7/8)
- splash-1242x2208.png (iPhone 6/7/8 Plus)
- splash-1125x2436.png (iPhone X/XS)
