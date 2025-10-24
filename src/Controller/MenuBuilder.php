<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Models\Article;
use Dionysosv2\Models\ArticleCategory;
use Dionysosv2\Controller\ArticleController;
use Dionysosv2\Controller\ArticleCategoryController;
use Dionysosv2\Views\Page;

class MenuBuilder extends Page
{
    private ArticleController $articleController;
    private ArticleCategoryController $categoryController;

    public function __construct()
    {
        parent::__construct();
        $this->articleController = new ArticleController();
        $this->categoryController = new ArticleCategoryController();
    }

    public function generatePdf(): void
    {
        $allCategories = $this->categoryController->getAllCategories();
        $allArticles = $this->articleController->getAllArticles();

        // Nach Kategorie gruppieren
        $groupedByCategory = [];
        foreach ($allArticles as $article) {
            $catName = $article->getCategory()->getName();
            if (!isset($groupedByCategory[$catName])) {
                $groupedByCategory[$catName] = [];
            }
            $groupedByCategory[$catName][] = $article;
        }

        // Nur Kategorien mit mindestens 3 Artikeln
        $filteredCategories = array_filter($groupedByCategory, fn($articles) => count($articles) >= 2);

        $flatArticles = [];
        foreach ($filteredCategories as $category => $articles) {
            $flatArticles[] = [
                'category' => $category,
                'articles' => $articles,
            ];
        }

        $pages = [];
        $currentPage = [];

        foreach ($flatArticles as $group) {
            $category = $group['category'];
            $articles = $group['articles'];
            $count = count($articles);

            $currentPage[] = ['category' => $category, 'articles' => $articles];
        }

        // Letzte Seite hinzufügen
        if (!empty($currentPage)) {
            $pages[] = $currentPage;
        }

        $imagePath = __DIR__ . '/../../public/assets/img/logo.png';
        $imgData = base64_encode(file_get_contents($imagePath));
        $src = 'data:image/png;base64,' . $imgData;

        ob_start();
        ?>
        <html>
        <head>
            <style>
                @page {
                    margin: 0;
                }
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 16px;
                    margin: 0;
                    padding: 0;
                    background-size: cover;
                }

                h1 {
                    text-align: center;
                    margin-bottom: 30px;
                }
                h2 {
                    margin-top: 20px;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 5px;
                }
                h2 title {
                    text-align: center;
                }
                .article {
                    margin-bottom: 20px;
                    page-break-inside: avoid;
                    max-width: 90%;
                }
                .article em {
                    font-size: 12px;
                }
                .price {
                    float: right;
                }
                .category {
                    padding-top: 10px;
                    page-break-inside: avoid;
                    margin-bottom: 20px;
                }

                .category h1 {
                    padding: 15px;
                    color: rgba(80, 0, 0, 0.7);
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
                }

                .category h2 {
                    color: rgba(80, 0, 0, 0.7);
                    font-weight: bold;
                    margin-bottom: 15px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #8B0000;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                }
                .page-content {
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .paper-wrapper {
                    background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/paper.jpg') ?>');
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-position: left;
                    width: 100%;
                    height: 100%;
                    padding: 10px;
                    box-sizing: border-box;
                }

                #start-page {
                    background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/paper.jpg') ?>');
                    background-size: cover;
                    background-repeat: no-repeat;
                }

                #start-page img {
                    margin-top: 20%;
                }

                #start-page h2 {
                    text-align: center;
                    padding-bottom: 37%;
                }

                .page-break {
                    page-break-after: always;
                }
                .logo-wrapper {
                    text-align: center;
                }
                .logo-wrapper img {
                    width: 60%;
                    max-width: 100%;
                }
            </style>
        </head>
        <body style="background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/leather.jpg') ?>'); background-size: cover;">

        <!-- Startseite -->
        <div id="start-page" >
            <div class="logo-wrapper">
                <img src="<?= $src ?>" alt="logo">
            </div>
            <h1>DIONYSOS</h1>
            <h2 class="title">Der Grieche Am Main</h2>
        </div>

        <!-- Inhaltsseiten -->
        <?php foreach ($pages as $pageIndex => $page): ?>
            <div class="page-content">
                <div class="paper-wrapper">
                    <?php foreach ($page as $group): ?>
                        <div class="category">
                            <h2><?= htmlspecialchars($group['category']) ?></h2>
                            <?php foreach ($group['articles'] as $article): ?>
                                <div class="article">
                                    <strong><?= htmlspecialchars($article->getPLU()) ?> </strong>
                                    <strong><?= htmlspecialchars($article->getName()) ?></strong>
                                    <span class="price"><?= number_format($article->getPrice(), 2) ?> €</span><br>
                                    <em><?= htmlspecialchars($article->getDescription()) ?></em>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($pageIndex + 1 < count($pages)): ?>
                <div class="page-break"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        </html>
        <?php

        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        file_put_contents(__DIR__ . '/../../public/speisekarte.pdf', $dompdf->output());
    }

    public function generateEnglishPdf(): void
    {
        $allCategories = $this->categoryController->getAllCategories();
        $allArticles = $this->articleController->getAllArticles();

        // Kategorien nach Code indizieren
        $categoryLabelsEn = [];
        foreach ($allCategories as $cat) {
            $categoryLabelsEn[$cat['code']] = $cat['label_en'] ?? $cat['label'];
        }

        ob_start();
        ?>
        <html>
        <head>
            <style>
                @page {
                    margin: 0;
                }
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 16px;
                    margin: 0;
                    padding: 0;
                    background-size: cover;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 30px;
                }
                h2 {
                    margin-top: 20px;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 5px;
                }
                .article {
                    margin-bottom: 20px;
                    page-break-inside: avoid;
                    max-width: 90%;
                }
                .article em {
                    font-size: 12px;
                }
                .price {
                    float: right;
                }
                .category {
                    padding-top: 10px;
                    page-break-inside: avoid;
                    margin-bottom: 20px;
                }
                .category h1 {
                    padding: 15px;
                    color: rgba(80, 0, 0, 0.7);
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
                }
                .category h2 {
                    color: rgba(80, 0, 0, 0.7);
                    font-weight: bold;
                    margin-bottom: 15px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #8B0000;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                }
                .page-content {
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .paper-wrapper {
                    background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/paper.jpg') ?>');
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-position: left;
                    width: 100%;
                    height: 100%;
                    padding: 10px;
                    box-sizing: border-box;
                }

                #start-page {
                    background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/paper.jpg') ?>');
                    background-size: cover;
                    background-repeat: no-repeat;
                }

                #start-page img {
                    margin-top: 20%;
                }

                #start-page h2 {
                    text-align: center;
                    padding-bottom: 37%;
                }

                .page-break {
                    page-break-after: always;
                }
                .logo-wrapper {
                    text-align: center;
                }
                .logo-wrapper img {
                    width: 60%;
                    max-width: 100%;
                }
            </style>
        </head>
        <body style="background-image: url('<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/leather.jpg') ?>'); background-size: cover;">

        <!-- Startseite -->
        <div id="start-page" >
            <div class="logo-wrapper">
                <img src="<?= $this->imageToBase64(__DIR__ . '/../../public/assets/img/dionysos-logo.png') ?>" alt="logo">
            </div>
            <h1>DIONYSOS</h1>
            <h2 class="title">The Greek at the Main</h2>
        </div>

        <!-- Inhaltsseiten -->
        <?php
        // Nach Kategorie gruppieren
        $groupedByCategory = [];
        foreach ($allArticles as $article) {
            $catObj = $article->getCategory();
            $catLabelEn = method_exists($catObj, 'getLocalizedName') ? $catObj->getLocalizedName('en') : ($categoryLabelsEn[$catObj->getCode()] ?? $catObj->getName());
            if (!isset($groupedByCategory[$catLabelEn])) {
                $groupedByCategory[$catLabelEn] = [];
            }
            $groupedByCategory[$catLabelEn][] = $article;
        }
        $filteredCategories = array_filter($groupedByCategory, fn($articles) => count($articles) >= 2);
        $flatArticles = [];
        foreach ($filteredCategories as $category => $articles) {
            $flatArticles[] = [
                'category' => $category,
                'articles' => $articles,
            ];
        }
        $pages = [];
        $currentPage = [];
        foreach ($flatArticles as $group) {
            $category = $group['category'];
            $articles = $group['articles'];
            $currentPage[] = ['category' => $category, 'articles' => $articles];
        }
        if (!empty($currentPage)) {
            $pages[] = $currentPage;
        }
        ?>
        <?php foreach ($pages as $pageIndex => $page): ?>
            <div class="page-content">
                <div class="paper-wrapper">
                    <?php foreach ($page as $group): ?>
                        <div class="category">
                            <h2><?= htmlspecialchars($group['category']) ?></h2>
                            <?php foreach ($group['articles'] as $article): ?>
                                <div class="article">
                                    <strong><?= htmlspecialchars($article->getPLU()) ?> </strong>
                                    <strong><?= htmlspecialchars($article->getLocalizedName('en')) ?></strong>
                                    <span class="price"><?= number_format($article->getPrice(), 2) ?> €</span><br>
                                    <em><?= htmlspecialchars($article->getLocalizedDescription('en')) ?></em>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($pageIndex + 1 < count($pages)): ?>
                <div class="page-break"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        </html>
        <?php
        $html = ob_get_clean();
        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();
        file_put_contents(__DIR__ . '/../../public/speisekarte-en.pdf', $dompdf->output());
    }

    private function imageToBase64(string $path): string
    {
        $imgData = base64_encode(file_get_contents($path));
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return "data:image/{$ext};base64,{$imgData}";
    }
}
