# 10 Interview Preparation Tasks & Modifications for Rewrite Rules

These tasks are designed to test your understanding of WordPress rewrite rules, PHP, OOP, and practical coding skills. Use them to prepare for technical interviews.

---

## Task 1: Add Query Variable Support
**Difficulty:** Medium | **Time:** 15-20 mins

### Requirement:
Extend the rewrite rules to support a custom `?year=` query variable for filtering movies by release year.

### What You Need To Do:
1. Create a new rewrite tag `%mw_year%` that accepts only digits
2. Modify the movie rewrite rule to capture an optional year parameter at the end: `/movie/{genre}/{post-name}-{id}/{year}/`
3. Update `filter_post_type_link()` to include the year in the generated URL if the post has a custom field `_movie_release_year`
4. Add a helper method `get_post_meta_value()` that retrieves meta values with fallback handling

### Learning Points:
- How to handle optional URL segments
- How to use post meta in URL generation
- Understanding query variable scope

### Sample Output:
- `/movie/action/avengers-123/2019/`
- `/movie/comedy/hangover-456/2009/`

---

## Task 2: Implement Multi-Taxonomy URL Support
**Difficulty:** Hard | **Time:** 30-40 mins

### Requirement:
Modify the person rewrite rules to support BOTH career AND country taxonomies in the URL.

### What You Need To Do:
1. Create a new rewrite tag `%mw_country%` for country slugs
2. Change the person URL pattern to: `/person/{career}/{country}/{post-name}-{id}/`
3. Update `build_person_link()` to fetch both career and country terms
4. Create a helper method `get_term_slug_by_priority()` that can handle multiple taxonomies with priority ordering
5. Add proper error handling if one taxonomy has no terms assigned

### Learning Points:
- Complex regex patterns with multiple capture groups
- Managing multiple taxonomies
- Priority-based fallback logic

### Sample Output:
- `/person/actor/usa/tom-cruise-123/`
- `/person/director/france/jean-luc-godard-456/`

---

## Task 3: Add Pagination Support
**Difficulty:** Medium | **Time:** 20-25 mins

### Requirement:
Create rewrite rules to support paginated movie listings like `/movies/page/2/`

### What You Need To Do:
1. Register a new rewrite rule for `/movies/page/{page-number}/`
2. Create a new rewrite tag `%mw_page%` that accepts only digits
3. Add a static method `get_movies_page_url()` that generates these paginated URLs
4. Implement proper validation to ensure page numbers are positive integers
5. Add a WordPress action hook that allows other plugins to filter the movies page URL

### Learning Points:
- Building listing/archive pages with rewrite rules
- Pagination handling in WordPress
- Hook system for extensibility

### Sample Output:
- `/movies/page/1/`
- `/movies/page/3/`
- `/movies/genre/action/page/2/`

---

## Task 4: Create a URL Slug Collision Handler
**Difficulty:** Hard | **Time:** 40-50 mins

### Requirement:
Implement a system to prevent URL collisions when multiple posts have similar slugs or when a movie and person share a name.

### What You Need To Do:
1. Create a new method `check_url_collision( string $url ): bool` that checks if a URL is already taken
2. Modify `build_movie_link()` and `build_person_link()` to detect collisions
3. If a collision is detected, append a suffix like `-movie-123` or `-person-456` to the URL
4. Add a private method `generate_unique_slug( string $base_slug, string $type ): string`
5. Log all collisions to a transient for debugging purposes

### Learning Points:
- Database queries in WordPress
- Caching with transients
- Defensive programming
- URL uniqueness validation

### Sample Output:
- If "avengers" slug exists as both movie and person:
  - `/movie/action/avengers-123/` (movie)
  - `/person/actor/avengers-456-person/` (person - modified)

---

## Task 5: Implement SEO-Friendly Breadcrumbs URL Structure
**Difficulty:** Medium | **Time:** 25-30 mins

### Requirement:
Modify URLs to include breadcrumb-style hierarchies for better SEO: `/movies/{genre}/{subgenre}/{post-name}-{id}/`

### What You Need To Do:
1. Register a new taxonomy called `rt-movie-subgenre` (assume it's already created)
2. Create rewrite tags for both `%mw_genre%` and `%mw_subgenre%`
3. Write a new rewrite rule that captures both hierarchy levels
4. Create a method `build_hierarchical_link()` that fetches both parent and child terms
5. Add validation to ensure the subgenre actually belongs to the selected genre

### Learning Points:
- Hierarchical taxonomy relationships
- Multi-level URL structures
- SEO optimization in URL design
- Term relationship validation

### Sample Output:
- `/movies/action/superhero/avengers-123/`
- `/movies/comedy/dark-comedy/hangover-456/`

---

## Task 6: Add Dynamic URL Rewriting Based on User Role
**Difficulty:** Hard | **Time:** 35-45 mins

### Requirement:
Create different URL structures for different user roles (public URLs vs. admin preview URLs).

### What You Need To Do:
1. Modify `filter_post_type_link()` to check current user role
2. For administrators, generate admin preview URLs: `/admin-preview/rt-movie/{id}/`
3. For regular users, keep standard URLs: `/movie/{genre}/{post-name}-{id}/`
4. Register separate rewrite rules for both URL patterns
5. Create a method `get_user_specific_link()` that handles this logic
6. Add a filter hook `rt_movie_link_by_role` to allow customization

### Learning Points:
- User role checking with WordPress
- Conditional URL generation
- Custom WordPress filters
- Admin vs. public-facing logic

### Sample Output:
- Public (user): `/movie/action/avengers-123/`
- Admin (user): `/admin-preview/rt-movie/123/`
- Editor (user): `/editor-preview/rt-movie/123/`

---

## Task 7: Implement Rewrite Rule Versioning System
**Difficulty:** Hard | **Time:** 45-60 mins

### Requirement:
Create a system that tracks rewrite rule versions and automatically handles upgrades when URL structures change.

### What You Need To Do:
1. Create a `const RULE_VERSION = 2;` in the class
2. Store the current rule version in the WordPress options table: `rt_rewrite_rules_version`
3. Add a method `upgrade_rewrite_rules()` that compares versions and applies migrations
4. Implement migration callbacks:
   - `migrate_v1_to_v2()`: Changes old URL format to new format
5. Update `flush_on_activate()` to check versions and run migrations
6. Create a debug method `get_rewrite_rule_info()` that returns current rules and version

### Learning Points:
- Database option handling
- Migration patterns
- Backward compatibility
- Version management in plugins

### Sample Migration:
- Old: `/movie/{id}/`
- New: `/movie/{genre}/{post-name}-{id}/`

---

## Task 8: Create a Rewrite Rule Tester/Debugger
**Difficulty:** Medium | **Time:** 20-30 mins

### Requirement:
Add debugging capabilities to test and validate rewrite rules without needing to visit the actual URLs.

### What You Need To Do:
1. Create a public static method `test_url_pattern( string $test_url ): array`
2. This method should test a URL against all registered rewrite rules
3. Return which rule matched and what query variables were captured
4. Create a method `get_all_rewrite_rules(): array` that returns all registered rules
5. Add a method `validate_regex( string $pattern ): bool` that validates regex syntax
6. Create a helper that generates sample URLs from rewrite rules

### Learning Points:
- Regex testing and validation
- WordPress rewrite rules internal structure
- Debugging utilities
- Test-driven development

### Sample Usage:
```php
$result = Rewrite_Rules::test_url_pattern('/movie/action/avengers-123/');
// Returns: ['matched' => true, 'rule' => '...', 'query_vars' => [...]]
```

---

## Task 9: Implement Trailing Slash Normalization
**Difficulty:** Medium | **Time:** 20-25 mins

### Requirement:
Handle trailing slashes intelligently - accept both `/movie/action/avengers-123` and `/movie/action/avengers-123/` but redirect to the canonical version.

### What You Need To Do:
1. Add rewrite rules for both trailing-slash and no-trailing-slash versions
2. Create a filter on `redirect_canonical` to handle redirects
3. Implement a method `get_canonical_url( \WP_Post $post ): string` that always returns the canonical form
4. Add an option `rt_trailing_slash_handling` to allow site admins to choose behavior
5. Create a WP-CLI command helper: `get_trailing_slash_status()` that shows current setting

### Learning Points:
- Canonical URL handling
- Redirect logic
- Settings management
- HTTP redirect codes

### Sample Behavior:
- User visits: `/movie/action/avengers-123` (no slash)
- Gets redirected to: `/movie/action/avengers-123/` (with slash)
- Both patterns are registered in rewrite rules

---

## Task 10: Build a Rewrite Rules Migration Tool (Advanced)
**Difficulty:** Very Hard | **Time:** 60-90 mins

### Requirement:
Create a comprehensive tool that safely migrates post permalinks when URL structures change, updating all internal links and external references.

### What You Need To Do:
1. Create a new class `Rewrite_Migration_Handler` that manages migrations
2. Implement `plan_migration( array $old_rules, array $new_rules ): array`
   - Returns a plan of what will change and impact analysis
3. Implement `execute_migration(): array`
   - Updates all post permalinks
   - Creates redirect rules from old URLs to new URLs
   - Updates internal links in post content
4. Create a method `generate_redirects_from_old_to_new()` that uses WordPress redirect functions
5. Add a rollback method `rollback_migration()` for safety
6. Implement logging with `log_migration_step( string $message, array $data )`
7. Add a progress tracker for large migrations

### Learning Points:
- Complex data migration patterns
- Bulk post processing
- Link manipulation
- Atomic operations and rollbacks
- Transaction-like behavior in WordPress
- Logging and monitoring
- Performance optimization for large datasets

### Sample Flow:
```
1. Backup current rewrite rules
2. Plan migration (show impact)
3. Update rewrite rules
4. Migrate post permalinks
5. Create 301 redirects from old URLs
6. Update internal links in content
7. Flush cache
8. Log results
```

---

## Bonus Challenges

### Challenge A: Performance Optimization
Optimize the rewrite rules to handle 100,000+ posts efficiently. How would you:
- Cache term lookups?
- Minimize database queries?
- Use transients effectively?

### Challenge B: Internationalization (i18n)
Extend the rewrite rules to support multiple languages:
- `/en/movie/action/avengers-123/`
- `/fr/movie/action/avengers-123/`
- `/es/movie/action/avengers-123/`

### Challenge C: REST API Integration
Create REST API endpoints that match your rewrite rule structure and return the same content as the HTML versions.

---

## Interview Tips

When solving these tasks, be prepared to discuss:

1. **Why you chose that approach** - Interview is testing your decision-making
2. **Edge cases you considered** - Shows defensive programming thinking
3. **Performance implications** - Large sites care about efficiency
4. **Security concerns** - Always sanitize/validate user input
5. **How it scales** - What happens with 1,000,000 posts?
6. **Testing strategy** - How would you test this code?
7. **Backward compatibility** - How do you upgrade without breaking existing installations?

## Resources

- WordPress Rewrite Rules: https://developer.wordpress.org/plugins/rewrite-rules/
- Regex Tester: https://regex101.com/
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/
- WP-CLI Documentation: https://developer.wordpress.org/cli/commands/

---

Good luck with your interview preparation! 🚀
