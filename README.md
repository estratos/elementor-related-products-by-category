# Related Products – Same Category Only

Lightweight WooCommerce plugin that **forces related products to be selected exclusively from the product’s main category**, preventing WooCommerce from mixing results based on tags or other automatic signals.  
Fully **compatible with Elementor Pro** and safe across updates.

---

## ✨ Features

- 🗂️ Uses **only the product’s primary category**
- 🔢 Displays **up to 4 related products**
- 🧩 Compatible with **WooCommerce** and **Elementor Pro**
- 🚫 Does not use tags to relate products
- ⚡ No custom SQL, no template overrides
- 🔁 Smart fallback if no valid products are found
- 🛡️ Update-safe and future-proof

---

## 📦 Installation

1. Create the plugin folder:

wp-content/plugins/elementor-related-products-by-category/


2. Create the plugin file: elementor-related-products-by-category-php


3. Paste the plugin code into the file.
4. Activate the plugin from **WP Admin → Plugins**.

---

## 🧠 How It Works

The plugin hooks into WooCommerce’s native filter:


and replaces the default related products logic with a controlled `WP_Query` that:

- Uses the same **primary category**
- Excludes the current product
- Respects product visibility and WooCommerce meta rules

This prevents WooCommerce from filling related products using tags, popularity, or other heuristics.

---

## ⚙️ Quick Configuration

### Change the number of related products

Edit this line in the plugin file:

```php
'posts_per_page' => 4,




