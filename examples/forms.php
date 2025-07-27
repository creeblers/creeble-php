<?php

require_once '../vendor/autoload.php';

use Creeble\Creeble;
use Creeble\Exceptions\ValidationException;

// Initialize client
$creeble = new Creeble('your-api-key');

try {
    // Get form configuration
    $form = $creeble->forms->getForm('your-endpoint', 'contact');
    echo "Form: " . $form['form_name'] . "\n";
    echo "Enabled: " . ($form['is_form_enabled'] ? 'Yes' : 'No') . "\n\n";

    // Submit form data
    $formData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello from PHP SDK!'
    ];

    $result = $creeble->forms->submit('your-endpoint', 'contact', $formData);
    echo "Submission successful!\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (ValidationException $e) {
    echo "Validation failed:\n";
    foreach ($e->getErrors() as $field => $errors) {
        echo "- {$field}: " . implode(', ', $errors) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example with validation
try {
    $formData = [
        'name' => 'Jane Smith',
        'email' => 'invalid-email',
        'message' => 'This will fail validation'
    ];

    $result = $creeble->forms->submitWithValidation('your-endpoint', 'contact', $formData);
    echo "This won't be reached due to validation error\n";

} catch (ValidationException $e) {
    echo "\nValidation example - caught error:\n";
    foreach ($e->getErrors() as $field => $errors) {
        echo "- {$field}: " . implode(', ', $errors) . "\n";
    }
}