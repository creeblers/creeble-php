# Creeble PHP SDK

The official PHP SDK for [Creeble](https://creeble.io) - Transform your Notion content into powerful, accessible APIs.

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Creeble\Creeble;

// Initialize the client
$creeble = new Creeble('your-api-key-here');

// Fetch all items from your project
$posts = $creeble->get('cms-abc123');

// Get a specific item by ID
$post = $creeble->find('cms-abc123', 'post-id-123');

// Submit to a form
$result = $creeble->forms->submit('cms-abc123', 'contact', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Search and filter content
$publishedPosts = $creeble->data->filter('cms-abc123', ['status' => 'published']);
```

## Basic Usage

### Initialize Client

```php
$creeble = new Creeble('your-api-key');

// With custom options
$creeble = new Creeble(
    apiKey: 'your-api-key',
    baseUrl: 'https://api.creeble.com', // Optional: custom base URL
    options: [
        'timeout' => 30,
        'connect_timeout' => 10,
    ]
);
```

### Fetch Data

```php
// Get all items (with automatic pagination)
$items = $creeble->data->list('your-endpoint');

// Get specific item by ID
$item = $creeble->data->get('your-endpoint', 'item-id');

// Paginated results
$page1 = $creeble->data->paginate('your-endpoint', page: 1, limit: 20);
$page2 = $creeble->data->paginate('your-endpoint', page: 2, limit: 20);
```

### Search & Filter

```php
// Search by text
$results = $creeble->data->search('cms-abc123', 'search query');

// Filter by specific fields
$published = $creeble->data->filter('cms-abc123', [
    'status' => 'published',
    'category' => 'technology'
]);

// Sort results
$recent = $creeble->data->sortBy('cms-abc123', 'created_at', 'desc');

// Get recent items
$latest = $creeble->data->recent('cms-abc123', limit: 10);
```

### Form Submissions

```php
// Get form configuration
$form = $creeble->forms->getForm('cms-abc123', 'contact');

// Submit form data
$result = $creeble->forms->submit('cms-abc123', 'contact', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello from PHP!'
]);

// Submit with client-side validation
try {
    $result = $creeble->forms->submitWithValidation('cms-abc123', 'contact', [
        'name' => 'John Doe',
        'email' => 'invalid-email'
    ]);
} catch (ValidationException $e) {
    foreach ($e->getErrors() as $field => $errors) {
        echo "{$field}: " . implode(', ', $errors) . "\n";
    }
}

// Working with Form models
use Creeble\Models\Form;

$formData = $creeble->forms->getForm('cms-abc123', 'contact');
$form = new Form($formData);

echo "Form: " . $form->getName() . "\n";
echo "Required fields: " . implode(', ', $form->getRequiredFields()) . "\n";
echo $form->renderHtmlFields();
```

### Project Information

```php
// Get project info
$info = $creeble->projects->info('cms-abc123');

// Get project schema/structure
$schema = $creeble->projects->schema('cms-abc123');

// Get available fields
$fields = $creeble->projects->fields('cms-abc123');

// Check if project exists
$exists = $creeble->projects->exists('cms-abc123');
```

## Advanced Usage

### Custom Parameters

```php
// Advanced filtering with multiple parameters
$results = $creeble->data->list('cms-abc123', [
    'status' => 'published',
    'category' => 'technology',
    'author' => 'john-doe',
    'limit' => 50,
    'sort' => 'created_at',
    'order' => 'desc',
    'fields' => 'title,content,author,created_at' // Only return specific fields
]);
```

### Error Handling

```php
use Creeble\Exceptions\AuthenticationException;
use Creeble\Exceptions\RateLimitException;
use Creeble\Exceptions\ValidationException;
use Creeble\Exceptions\CreebleException;

try {
    $posts = $creeble->get('cms-abc123');
} catch (AuthenticationException $e) {
    // Invalid API key
    echo "Authentication failed: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Rate limit exceeded
    echo "Rate limit exceeded. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (ValidationException $e) {
    // Invalid request parameters
    echo "Validation failed: " . $e->getMessage();
    print_r($e->getErrors());
} catch (CreebleException $e) {
    // General API error
    echo "API error: " . $e->getMessage();
}
```

### Connection Testing

```php
// Test API connection
if ($creeble->ping()) {
    echo "Connection successful!";
} else {
    echo "Connection failed!";
}
```

## Real-World Examples

### Blog/CMS Integration

```php
<?php

class BlogService 
{
    private $creeble;
    
    public function __construct(string $apiKey)
    {
        $this->creeble = new Creeble($apiKey);
    }
    
    public function getPublishedPosts(int $page = 1): array
    {
        return $this->creeble->data->paginate('cms-abc123', $page, 10, [
            'status' => 'published'
        ]);
    }
    
    public function getPostBySlug(string $slug): ?array
    {
        $results = $this->creeble->data->filter('cms-abc123', [
            'slug' => $slug,
            'status' => 'published'
        ]);
        
        return $results[0] ?? null;
    }
    
    public function searchPosts(string $query): array
    {
        return $this->creeble->data->search('cms-abc123', $query, [
            'status' => 'published'
        ]);
    }
    
    public function getPostsByCategory(string $category): array
    {
        return $this->creeble->data->filter('cms-abc123', [
            'category' => $category,
            'status' => 'published'
        ]);
    }
}

// Usage
$blog = new BlogService('your-api-key');

// Get latest posts
$posts = $blog->getPublishedPosts();

// Get specific post
$post = $blog->getPostBySlug('my-blog-post');

// Search posts
$searchResults = $blog->searchPosts('php tutorial');
```

### E-commerce Product Catalog

```php
<?php

class ProductCatalog
{
    private $creeble;
    
    public function __construct(string $apiKey)
    {
        $this->creeble = new Creeble($apiKey);
    }
    
    public function getProducts(array $filters = []): array
    {
        $defaultFilters = ['status' => 'active'];
        $filters = array_merge($defaultFilters, $filters);
        
        return $this->creeble->data->filter('products-xyz789', $filters);
    }
    
    public function getProductById(string $id): ?array
    {
        try {
            return $this->creeble->data->get('products-xyz789', $id);
        } catch (CreebleException $e) {
            return null;
        }
    }
    
    public function getProductsByCategory(string $category): array
    {
        return $this->getProducts(['category' => $category]);
    }
    
    public function getFeaturedProducts(int $limit = 8): array
    {
        return $this->creeble->data->filter('products-xyz789', [
            'featured' => true,
            'status' => 'active',
            'limit' => $limit
        ]);
    }
    
    public function searchProducts(string $query): array
    {
        return $this->creeble->data->search('products-xyz789', $query, [
            'status' => 'active'
        ]);
    }
}
```

## API Reference

### Data Methods

| Method | Description |
|--------|-------------|
| `list($endpoint, $params)` | Get all items from endpoint |
| `get($endpoint, $id)` | Get specific item by ID |
| `search($endpoint, $query, $filters)` | Search items |
| `filter($endpoint, $filters)` | Filter items by fields |
| `paginate($endpoint, $page, $limit, $filters)` | Get paginated results |
| `sortBy($endpoint, $field, $direction, $filters)` | Sort items |
| `recent($endpoint, $limit)` | Get recent items |
| `exists($endpoint, $id)` | Check if item exists |

### Projects Methods

| Method | Description |
|--------|-------------|
| `info($endpoint)` | Get project information |
| `schema($endpoint)` | Get project schema |
| `stats($endpoint)` | Get project statistics |
| `fields($endpoint)` | Get available fields |
| `exists($endpoint)` | Check if project exists |

### Forms Methods

| Method | Description |
|--------|-------------|
| `getForm($endpoint, $formSlug)` | Get form configuration and schema |
| `submit($endpoint, $formSlug, $data)` | Submit data to form |
| `getSchema($endpoint, $formSlug)` | Get form schema only |
| `submitWithValidation($endpoint, $formSlug, $data)` | Submit with client-side validation |
| `validateFormData($schema, $data)` | Validate form data locally |

## Common Parameters

### Filtering
- `status` - Filter by status (e.g., 'published', 'draft')
- `category` - Filter by category
- `author` - Filter by author
- `tags` - Filter by tags
- `date_from` - Filter from date
- `date_to` - Filter to date

### Pagination
- `page` - Page number (1-based)
- `limit` - Items per page
- `offset` - Offset for results

### Sorting
- `sort` - Field to sort by
- `order` - Sort direction ('asc' or 'desc')

### Fields
- `fields` - Comma-separated list of fields to return

## Error Handling

The SDK throws specific exceptions for different error types:

- `AuthenticationException` - Invalid API key
- `RateLimitException` - Rate limit exceeded
- `ValidationException` - Invalid request parameters  
- `CreebleException` - General API errors

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client

## Support

- [Documentation](https://docs.creeble.io)
- [API Reference](https://api.creeble.io/docs)
- [Support](mailto:support@creeble.io)

## License

MIT License. See [LICENSE](LICENSE) for details.
