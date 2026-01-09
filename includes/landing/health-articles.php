<?php
/**
 * Health Articles Component - Landing Page
 * แสดงบทความสุขภาพล่าสุด
 */

require_once __DIR__ . '/../../classes/HealthArticleService.php';
$articleService = new HealthArticleService($db, $lineAccountId);
$articles = $articleService->getPublishedArticles(6);

if (empty($articles)) return;
?>

<!-- Health Articles Section -->
<section class="health-articles-section" id="health-articles">
    <div class="container">
        <div class="section-title">
            <h2>📚 บทความสุขภาพ</h2>
            <p>ความรู้ดีๆ เพื่อสุขภาพของคุณ</p>
        </div>
        
        <div class="articles-grid">
            <?php foreach ($articles as $article): ?>
            <a href="article.php?slug=<?= htmlspecialchars($article['slug']) ?>" class="article-card">
                <div class="article-image">
                    <?php if (!empty($article['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($article['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($article['title']) ?>"
                         loading="lazy">
                    <?php else: ?>
                    <div class="article-placeholder">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($article['category_name'])): ?>
                    <span class="article-category"><?= htmlspecialchars($article['category_name']) ?></span>
                    <?php endif; ?>
                    
                    <?php if ($article['is_featured']): ?>
                    <span class="article-badge">แนะนำ</span>
                    <?php endif; ?>
                </div>
                
                <div class="article-content">
                    <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                    
                    <?php if (!empty($article['excerpt'])): ?>
                    <p class="article-excerpt"><?= htmlspecialchars(mb_substr($article['excerpt'], 0, 100)) ?>...</p>
                    <?php endif; ?>
                    
                    <div class="article-meta">
                        <?php if (!empty($article['author_name'])): ?>
                        <span class="article-author">
                            <i class="fas fa-user-md"></i>
                            <?= htmlspecialchars($article['author_name']) ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($article['published_at'])): ?>
                        <span class="article-date">
                            <i class="fas fa-calendar"></i>
                            <?= date('d M Y', strtotime($article['published_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="view-all-articles">
            <a href="articles.php" class="btn btn-outline-primary">
                <i class="fas fa-book-open"></i>
                ดูบทความทั้งหมด
            </a>
        </div>
    </div>
</section>

<style>
/* Health Articles Section */
.health-articles-section {
    padding: 48px 0;
    background: #f8fafc;
}

@media (min-width: 1024px) {
    .health-articles-section {
        padding: 64px 0;
    }
}

.articles-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 640px) {
    .articles-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .articles-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
}

/* Article Card */
.article-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    display: block;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

.article-image {
    aspect-ratio: 16/9;
    background: #e5e7eb;
    position: relative;
    overflow: hidden;
}

.article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.article-card:hover .article-image img {
    transform: scale(1.05);
}

.article-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light) 0%, #e5e7eb 100%);
    color: var(--primary);
    font-size: 40px;
    opacity: 0.5;
}

.article-category {
    position: absolute;
    bottom: 12px;
    left: 12px;
    padding: 4px 12px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.article-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    background: var(--primary);
    color: white;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.article-content {
    padding: 16px;
}

.article-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.article-excerpt {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.article-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 12px;
    color: #9ca3af;
}

.article-meta i {
    margin-right: 4px;
}

.article-author {
    display: flex;
    align-items: center;
}

.article-date {
    display: flex;
    align-items: center;
}

/* View All Button */
.view-all-articles {
    text-align: center;
    margin-top: 32px;
}
</style>
