# CIPIT Publications
## A Curated Publication Management System for WordPress
A high-end WordPress plugin developed for CIPIT to showcase research papers, policy briefs, and fellowship series. Built with a focus on the Golden Ratio (), A4-ratio card layouts, and adaptive tech aesthetics.

# Installation
1. Upload the cipit-publications folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Crucial Step: Navigate to Settings > Permalinks and click Save Changes to flush the rewrite rules (prevents 404 errors on detail pages)

# Shortcode Usage
Use the `[curated_publication]` shortcode to display your lists.
#### Basic Usage
Display the latest 5 publications:
`[curated_publication show="5"]`

#### Filtering by Group
Display only items from the "Africa Data Fellowship" (use the category slug):
`[curated_publication group="africa-data-fellowship-series" show="3"]`

#### With Pagination
Enable page navigation for long lists:
`[curated_publication show="6" pagination="true"]`

#### Custom Ordering
Display alphabetically by title:
`[curated_publication order="ASC" orderby="title"]`

#### Complete Example
[curated_publication group="africa-data-fellowship-series" show="5" pagination="true" order="DESC" orderby="date"]

