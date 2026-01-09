<?php
/**
 * HealthArticleService - จัดการบทความสุขภาพ
 */

class HealthArticleService {
    private $db;
    private $lineAccountId;
    
    public function __construct(PDO $db, ?int $lineAccountId = null) {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }
    
    /**
     * Get published articles for landing page
     */
    public function getPublishedArticles(int $limit = 6, ?int $categoryId = null): array {
        try {
            $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.is_published = 1";
            $params = [];
            
            if ($this->lineAccountId !== null) {
                $sql .= " AND (a.line_account_id = ? OR a.line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            
            if ($categoryId) {
                $sql .= " AND a.category_id = ?";
                $params[] = $categoryId;
            }
            
            $sql .= " ORDER BY a.is_featured DESC, a.published_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get featured articles
     */
    public function getFeaturedArticles(int $limit = 3): array {
        try {
            $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.is_published = 1 AND a.is_featured = 1";
            $params = [];
            
            if ($this->lineAccountId !== null) {
                $sql .= " AND (a.line_account_id = ? OR a.line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            
            $sql .= " ORDER BY a.published_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get article by slug
     */
    public function getBySlug(string $slug): ?array {
        try {
            $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.slug = ? AND a.is_published = 1";
            $params = [$slug];
            
            if ($this->lineAccountId !== null) {
                $sql .= " AND (a.line_account_id = ? OR a.line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article) {
                // Increment view count
                $this->incrementViewCount($article['id']);
            }
            
            return $article ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get article by ID
     */
    public function getById(int $id): ?array {
        try {
            $sql = "SELECT a.*, c.name as category_name
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get all articles for admin
     */
    public function getAllForAdmin(): array {
        try {
            $sql = "SELECT a.*, c.name as category_name
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE 1=1";
            $params = [];
            
            if ($this->lineAccountId !== null) {
                $sql .= " AND (a.line_account_id = ? OR a.line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            
            $sql .= " ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get all categories
     */
    public function getCategories(): array {
        try {
            $sql = "SELECT * FROM health_article_categories WHERE is_active = 1 ORDER BY sort_order ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Create article
     */
    public function create(array $data): int {
        $slug = $this->generateSlug($data['title']);
        
        $stmt = $this->db->prepare("
            INSERT INTO health_articles 
            (line_account_id, category_id, title, slug, excerpt, content, featured_image, 
             author_name, author_title, author_image, tags, meta_title, meta_description, 
             is_featured, is_published, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $publishedAt = !empty($data['is_published']) ? date('Y-m-d H:i:s') : null;
        $tags = !empty($data['tags']) ? json_encode($data['tags']) : null;
        
        $stmt->execute([
            $this->lineAccountId,
            $data['category_id'] ?? null,
            $data['title'],
            $slug,
            $data['excerpt'] ?? null,
            $data['content'],
            $data['featured_image'] ?? null,
            $data['author_name'] ?? null,
            $data['author_title'] ?? null,
            $data['author_image'] ?? null,
            $tags,
            $data['meta_title'] ?? $data['title'],
            $data['meta_description'] ?? $data['excerpt'],
            $data['is_featured'] ?? 0,
            $data['is_published'] ?? 0,
            $publishedAt
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update article
     */
    public function update(int $id, array $data): bool {
        $article = $this->getById($id);
        if (!$article) return false;
        
        // Update slug if title changed
        $slug = $article['slug'];
        if ($data['title'] !== $article['title']) {
            $slug = $this->generateSlug($data['title'], $id);
        }
        
        $tags = !empty($data['tags']) ? json_encode($data['tags']) : null;
        
        // Set published_at if publishing for first time
        $publishedAt = $article['published_at'];
        if (!empty($data['is_published']) && empty($article['published_at'])) {
            $publishedAt = date('Y-m-d H:i:s');
        }
        
        $stmt = $this->db->prepare("
            UPDATE health_articles SET
                category_id = ?, title = ?, slug = ?, excerpt = ?, content = ?,
                featured_image = ?, author_name = ?, author_title = ?, author_image = ?,
                tags = ?, meta_title = ?, meta_description = ?,
                is_featured = ?, is_published = ?, published_at = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['category_id'] ?? null,
            $data['title'],
            $slug,
            $data['excerpt'] ?? null,
            $data['content'],
            $data['featured_image'] ?? null,
            $data['author_name'] ?? null,
            $data['author_title'] ?? null,
            $data['author_image'] ?? null,
            $tags,
            $data['meta_title'] ?? $data['title'],
            $data['meta_description'] ?? $data['excerpt'],
            $data['is_featured'] ?? 0,
            $data['is_published'] ?? 0,
            $publishedAt,
            $id
        ]);
    }
    
    /**
     * Delete article
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM health_articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Toggle publish status
     */
    public function togglePublish(int $id): bool {
        $article = $this->getById($id);
        if (!$article) return false;
        
        $newStatus = $article['is_published'] ? 0 : 1;
        $publishedAt = $newStatus && !$article['published_at'] ? date('Y-m-d H:i:s') : $article['published_at'];
        
        $stmt = $this->db->prepare("UPDATE health_articles SET is_published = ?, published_at = ? WHERE id = ?");
        return $stmt->execute([$newStatus, $publishedAt, $id]);
    }
    
    /**
     * Toggle featured status
     */
    public function toggleFeatured(int $id): bool {
        $stmt = $this->db->prepare("UPDATE health_articles SET is_featured = NOT is_featured WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Increment view count
     */
    private function incrementViewCount(int $id): void {
        try {
            $stmt = $this->db->prepare("UPDATE health_articles SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {}
    }
    
    /**
     * Generate URL-friendly slug
     */
    private function generateSlug(string $title, ?int $excludeId = null): string {
        // Convert Thai to transliteration or use hash
        $slug = preg_replace('/[^a-zA-Z0-9\x{0E00}-\x{0E7F}]+/u', '-', $title);
        $slug = trim($slug, '-');
        $slug = mb_strtolower($slug);
        
        // If mostly Thai, add timestamp for uniqueness
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $slug)) {
            $slug = substr(md5($title), 0, 8) . '-' . time();
        }
        
        // Check uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM health_articles WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
    
    /**
     * Get related articles
     */
    public function getRelatedArticles(int $articleId, int $limit = 3): array {
        try {
            $article = $this->getById($articleId);
            if (!$article) return [];
            
            $sql = "SELECT a.*, c.name as category_name
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.is_published = 1 AND a.id != ?";
            $params = [$articleId];
            
            if ($article['category_id']) {
                $sql .= " AND a.category_id = ?";
                $params[] = $article['category_id'];
            }
            
            $sql .= " ORDER BY a.published_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Search articles
     */
    public function search(string $query, int $limit = 10): array {
        try {
            $sql = "SELECT a.*, c.name as category_name
                    FROM health_articles a
                    LEFT JOIN health_article_categories c ON a.category_id = c.id
                    WHERE a.is_published = 1 
                    AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)
                    ORDER BY a.published_at DESC LIMIT ?";
            
            $searchTerm = "%{$query}%";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getCount(): int {
        try {
            $sql = "SELECT COUNT(*) FROM health_articles WHERE is_published = 1";
            $params = [];
            if ($this->lineAccountId !== null) {
                $sql .= " AND (line_account_id = ? OR line_account_id IS NULL)";
                $params[] = $this->lineAccountId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
