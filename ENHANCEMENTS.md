# Buyer Experience Enhancements

This version adds the following buyer-facing features:

1. **Wishlist / favourites**
   - Heart buttons on the homepage, product listing, and product detail page.
   - Dedicated `buyer/wishlist.php` page.
   - Remove saved products or quick-add the first available variation to the cart.

2. **Professional product sorting**
   - Newest, most popular, highest rated, price low-to-high, price high-to-low, name A-Z, and oldest.

3. **Related products**
   - Product detail pages show up to four products from the same category.

4. **Recently viewed products**
   - Stored in the PHP session.
   - Displayed on product detail pages and the homepage.

5. **Improved size guide**
   - Responsive modal with separate guidance for women, men, kids, footwear, and accessories.

6. **Dynamic stock indicators**
   - In-stock, low-stock, and out-of-stock labels.
   - Exact stock quantity shown after selecting a size and colour.
   - Quantity input is limited to available stock.

7. **Review summary**
   - Average rating, review count, and rating-distribution bars on the product detail page.

## Database update

Run:

`http://localhost/fashion_store/setup_db.php`

This creates or upgrades the `wishlists` table without deleting existing data.

## Quick test checklist

- Sign in as `buyer_demo` / `password123`.
- Save and remove a product using the heart icon.
- Open Buyer Dashboard → Wishlist.
- Test every Sort By option on the product listing page.
- Open several products and verify Recently Viewed.
- Verify You May Also Like appears on product details.
- Select size and colour and confirm the exact stock message changes.
- Open Size Guide and test closing by X, outside click, and Escape.
