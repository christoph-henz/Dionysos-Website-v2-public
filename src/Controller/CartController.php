<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Models\Cart;
use Exception;

class CartController {
    private Cart $cart;
    private ArticleController $articleController;

    public function __construct() {
        $this->articleController = new ArticleController();

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = new Cart();
        }
        $this->cart = $_SESSION['cart'];
    }

    public function getCartItems(): array {
        if (!isset($_SESSION['cart'])) {
            return [];
        }

        return array_map(function($item) {
            $basePrice = $item['article']->getPrice();
            $optionsPrice = 0;
            $options = [];
            
            // Include options if they exist
            if (isset($item['options']) && !empty($item['options'])) {
                foreach ($item['options'] as $option) {
                    $options[] = [
                        'name' => $option['name'],
                        'price' => $option['price']
                    ];
                    $optionsPrice += $option['price'];
                }
            }
            
            $totalItemPrice = ($basePrice + $optionsPrice) * $item['quantity'];
            
            return [
                'id' => $item['article']->getId(),
                'plu' => $item['article']->getPLU(),
                'name' => $item['article']->getName(),
                'price' => $basePrice,
                'quantity' => $item['quantity'],
                'options' => $options,
                'options_price' => $optionsPrice,
                'total' => $totalItemPrice
            ];
        }, $this->cart->getItems());
    }
    /**
     * Handles the addition or removal of items in the cart.
     *
     * @param int $articleId The ID of the article to add or remove.
     * @param int $delta The change in quantity (positive to add, negative to remove).
     * @return array An array containing the updated cart items and total.
     * @throws Exception If the article is not found.
     */
    public function handleQuantityChange(int $articleId, int $delta): array {
        // Special case for initial cart load
        if ($articleId === 0 && $delta === 0) {
            return $this->getCartResponse();
        }

        // Regular cart update
        $article = $this->articleController->getArticleById($articleId);
        if (!$article) {
            throw new Exception("Article not found");
        }

        if ($delta > 0) {
            $this->cart->addItem($article, $delta);
        } else {
            $this->cart->updateQuantity($articleId, $delta);
        }

        $_SESSION['cart'] = $this->cart;

        return $this->getCartResponse();
    }

    /**
     * Handles the addition or removal of items in the cart with options.
     *
     * @param int $articleId The ID of the article to add or remove.
     * @param int $delta The change in quantity (positive to add, negative to remove).
     * @param array $options The selected options for the article.
     * @return array An array containing the updated cart items and total.
     * @throws Exception If the article is not found.
     */
    public function handleQuantityChangeWithOptions(int $articleId, int $delta, array $options): array {
        $article = $this->articleController->getArticleById($articleId);
        if (!$article) {
            throw new Exception("Article not found");
        }

        // Create a unique cart key based on article and options
        $optionsKey = $this->generateOptionsKey($options);
        $cartKey = $articleId . '_' . $optionsKey;

        // Get option details for display
        $optionController = new OptionController();
        $optionDetails = [];
        $totalPriceModifier = 0;

        foreach ($options as $optionData) {
            $option = $optionController->getOptionById($optionData['optionId']);
            if ($option) {
                $optionDetails[] = [
                    'id' => $option->getId(),
                    'name' => $option->getName(),
                    'price' => $option->getPriceModifier()
                ];
                $totalPriceModifier += $option->getPriceModifier();
            }
        }

        if ($delta > 0) {
            $this->cart->addItemWithOptions($article, $delta, $optionDetails, $cartKey);
        } else {
            $this->cart->updateQuantityByKey($cartKey, $delta);
        }

        $_SESSION['cart'] = $this->cart;

        return $this->getCartResponse();
    }

    /**
     * Generates a unique key for the options combination.
     */
    private function generateOptionsKey(array $options): string {
        if (empty($options)) {
            return 'no_options';
        }
        
        $optionIds = array_map(function($option) {
            return $option['optionId'];
        }, $options);
        sort($optionIds);
        
        return md5(implode('_', $optionIds));
    }

    /**
     * Returns the standardized cart response.
     */
    private function getCartResponse(): array {
        return [
            'items' => array_values(array_map(function($key, $item) {
                $response = [
                    'id' => $item['article']->getId(),
                    'cart_key' => $key,
                    'plu' => $item['article']->getPLU(),
                    'name' => $item['article']->getName(),
                    'quantity' => $item['quantity'],
                    'base_price' => $item['article']->getPrice(),
                    'total' => isset($item['total']) ? $item['total'] : $item['article']->getPrice() * $item['quantity']
                ];

                // Add options if they exist
                if (isset($item['options']) && !empty($item['options'])) {
                    $response['options'] = $item['options'];
                    $response['display_name'] = $item['article']->getName() . ' (' . 
                        implode(', ', array_map(function($opt) {
                            return $opt['name'];
                        }, $item['options'])) . ')';
                } else {
                    $response['display_name'] = $item['article']->getName();
                }

                return $response;
            }, array_keys($this->cart->getItems()), $this->cart->getItems())),
            'total' => $this->cart->getTotal()
        ];
    }
    /**
     * Handles quantity changes by cart key (for items with options).
     */
    public function handleQuantityChangeByKey(string $cartKey, int $delta): array {
        if ($delta > 0) {
            // For adding, we need to parse the key to get article ID and options
            $parts = explode('_', $cartKey);
            $articleId = (int)$parts[0];
            
            if (count($parts) > 1 && $parts[1] !== 'no') {
                // This is an item with options, we need to handle it differently
                // For now, just update the existing item
                $this->cart->updateQuantityByKey($cartKey, $delta);
            } else {
                // Regular item
                $article = $this->articleController->getArticleById($articleId);
                if ($article) {
                    $this->cart->addItem($article, $delta);
                }
            }
        } else {
            $this->cart->updateQuantityByKey($cartKey, $delta);
        }

        $_SESSION['cart'] = $this->cart;
        return $this->getCartResponse();
    }

    public function clearCart(): void {
        if (isset($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
        $this->cart = new Cart();
    }

    /**
     * Returns whether the cart is empty.
     *
     * @return bool True if the cart is empty, false otherwise.
     */
    public function isEmpty(): bool {
        return empty($this->cart->getItems());
    }
}