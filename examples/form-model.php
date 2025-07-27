<?php

require_once '../vendor/autoload.php';

use Creeble\Creeble;
use Creeble\Models\Form;

// Initialize client
$creeble = new Creeble('your-api-key');

try {
    // Get form and wrap in model
    $formData = $creeble->forms->getForm('your-endpoint', 'contact');
    $form = new Form($formData);

    echo "Form Information:\n";
    echo "Name: " . $form->getName() . "\n";
    echo "Slug: " . $form->getSlug() . "\n";
    echo "Enabled: " . ($form->isEnabled() ? 'Yes' : 'No') . "\n";
    echo "Description: " . $form->getDescription() . "\n\n";

    echo "Form Fields:\n";
    foreach ($form->getFields() as $fieldName => $fieldConfig) {
        $type = $fieldConfig['type'] ?? 'text';
        $required = $form->isFieldRequired($fieldName) ? ' (required)' : '';
        echo "- {$fieldName}: {$type}{$required}\n";
        
        // Show options for select fields
        if (in_array($type, ['select', 'multi_select'])) {
            $options = $form->getFieldOptions($fieldName);
            if (!empty($options)) {
                echo "  Options: " . implode(', ', array_column($options, 'name')) . "\n";
            }
        }
    }

    echo "\nRequired Fields: " . implode(', ', $form->getRequiredFields()) . "\n\n";

    // Generate HTML form
    echo "HTML Form Fields:\n";
    echo $form->renderHtmlFields() . "\n";

    // Generate empty form data structure
    echo "Empty Form Data Structure:\n";
    echo json_encode($form->getEmptyFormData(), JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}