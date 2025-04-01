=== AI Review Generator ===
Contributors: Mikheili Nakeuri
Tags: ai, review, generator, deepseek, mistral, llama, openrouter, woocommerce
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically generate AI-powered reviews for WordPress posts and WooCommerce products using free or low-cost AI models.

== Description ==

AI Review Generator is a powerful WordPress plugin that automatically creates high-quality reviews for your blog posts and WooCommerce products using AI technology. The plugin is optimized for free or low-cost AI models like DeepSeek, Mistral, LLaMA 3, and OpenRouter.

### Key Features

* **AI-Powered Automatic Review Generation:** Automatically create reviews based on post titles, product names, descriptions, and metadata.
* **Multiple AI Model Support:** Connect to various AI models, including free and budget-friendly options.
* **Customizable Review Content:** Configure review tone, word count, and structure.
* **Star Ratings:** Automatically generate star ratings based on AI evaluation.
* **Automated Design & Theming:** Review boxes automatically match your site's color scheme.
* **SEO Optimization:** Includes Schema Markup (JSON-LD) for Google Rich Snippets.
* **Smart Caching:** Reduce costs with intelligent API call caching.
* **User-Friendly Admin Dashboard:** Easily manage settings and generated reviews.

### Supported AI Models

* **DeepSeek AI** - Free AI model with good quality results
* **Mistral 7B** - High-quality open source model
* **LLaMA 3 (Meta AI)** - Meta's open source model, self-hosted option available
* **OpenRouter** - Multi-model API with various options

### Use Cases

* Automatically generate product reviews for your WooCommerce store
* Create content reviews for your blog posts
* Generate opinions on news articles or tutorial posts
* Add professional-looking reviews to your website without manual writing

== Installation ==

1. Upload the `ai-review-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'AI Reviews' > 'Settings' to configure the plugin
4. Set up your preferred AI model and API keys
5. Customize review settings based on your preferences
6. Enable auto-generation for posts and products

== Frequently Asked Questions ==

= Do I need to provide my own API keys? =

Yes, you'll need to provide API keys for the AI models you want to use. The plugin supports several models, including some with free tiers, but you need to sign up for those services separately.

= Will this work with WooCommerce? =

Yes! The plugin is designed to work with both standard WordPress posts and WooCommerce products.

= Can I edit the AI-generated reviews? =

Absolutely. You can edit any generated review from the 'AI Reviews' > 'Reviews' page in your dashboard.

= Can I customize how the reviews look? =

Yes, the plugin provides extensive styling options. You can customize colors, borders, fonts, and more to match your site's design.

= Does this support dark mode? =

Yes, the plugin includes built-in dark mode support that automatically adapts to your users' preferences.

= How can I reduce API costs? =

The plugin includes smart caching to minimize API calls. You can also adjust the word count and choose more cost-effective AI models.

== Screenshots ==

1. Dashboard overview
2. AI-generated review displayed on a post
3. Admin settings page
4. WooCommerce product review example
5. Review style customization options

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Example API Keys and Default Settings ==

For testing purposes, you can use these example API key formats (please obtain actual keys from the respective services):

* **DeepSeek AI:** `sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
* **Mistral AI:** `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
* **OpenRouter:** `sk-or-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

Default settings are pre-configured for optimal performance, but we recommend:

1. Start with the DeepSeek AI model which offers a free tier
2. Use the "professional" tone and "pros_cons" structure
3. Set word count between 200-500 words for best results
4. Enable smart caching with 24-hour expiration

For WooCommerce integration, you'll need to have WooCommerce installed and activated before this plugin can generate product reviews.
