<?php

declare(strict_types=1);

namespace Creeble\Endpoints;

use Creeble\Exceptions\ValidationException;
use Creeble\Http\Client;

/**
 * Forms endpoint for submitting data to Creeble forms
 */
class Forms
{
    public function __construct(
        private readonly Client $client
    ) {}

    /**
     * Get form schema and settings
     * 
     * @param string $endpoint Project endpoint
     * @param string $formSlug Form slug
     * @return array
     */
    public function getForm(string $endpoint, string $formSlug): array
    {
        return $this->client->get("/v1/{$endpoint}/forms/{$formSlug}");
    }

    /**
     * Submit data to a form
     * 
     * @param string $endpoint Project endpoint
     * @param string $formSlug Form slug
     * @param array $formData Form submission data
     * @return array
     */
    public function submit(string $endpoint, string $formSlug, array $formData): array
    {
        return $this->client->post("/v1/{$endpoint}/forms/{$formSlug}", $formData);
    }

    /**
     * Get form schema only
     * 
     * @param string $endpoint Project endpoint
     * @param string $formSlug Form slug
     * @return array
     */
    public function getSchema(string $endpoint, string $formSlug): array
    {
        $response = $this->getForm($endpoint, $formSlug);
        return $response['schema'] ?? $response;
    }

    /**
     * Submit form with validation
     * 
     * @param string $endpoint Project endpoint
     * @param string $formSlug Form slug
     * @param array $formData Form submission data
     * @return array
     * @throws ValidationException
     */
    public function submitWithValidation(string $endpoint, string $formSlug, array $formData): array
    {
        // Get form schema first
        $form = $this->getForm($endpoint, $formSlug);
        
        // Validate locally
        $validation = $this->validateFormData($form['schema'] ?? [], $formData);
        if (!$validation['valid']) {
            throw new ValidationException('Form validation failed', $validation['errors']);
        }
        
        // Submit if valid
        return $this->submit($endpoint, $formSlug, $formData);
    }

    /**
     * Validate form data locally before submission
     * 
     * @param array $schema Form schema
     * @param array $formData Form data to validate
     * @return array{valid: bool, errors: array}
     */
    public function validateFormData(array $schema, array $formData): array
    {
        $errors = [];
        
        // Check required fields
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $fieldName => $fieldConfig) {
                $value = $formData[$fieldName] ?? null;
                
                // Check required
                if (($fieldConfig['required'] ?? false) && empty($value)) {
                    $errors[$fieldName][] = "The {$fieldName} field is required.";
                }
                
                // Validate field types
                if (!empty($value)) {
                    $fieldErrors = $this->validateFieldType($fieldName, $value, $fieldConfig);
                    if (!empty($fieldErrors)) {
                        $errors[$fieldName] = array_merge($errors[$fieldName] ?? [], $fieldErrors);
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate a specific field type
     * 
     * @param string $fieldName Field name
     * @param mixed $value Field value
     * @param array $fieldConfig Field configuration
     * @return array
     */
    private function validateFieldType(string $fieldName, mixed $value, array $fieldConfig): array
    {
        $errors = [];
        $type = $fieldConfig['type'] ?? 'text';
        
        switch ($type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "The {$fieldName} must be a valid email address.";
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = "The {$fieldName} must be a valid URL.";
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = "The {$fieldName} must be a number.";
                }
                break;
                
            case 'phone_number':
                if (!$this->isValidPhone($value)) {
                    $errors[] = "The {$fieldName} must be a valid phone number.";
                }
                break;
                
            case 'date':
                if (!$this->isValidDate($value)) {
                    $errors[] = "The {$fieldName} must be a valid date.";
                }
                break;
                
            case 'select':
                if (isset($fieldConfig['options']) && is_array($fieldConfig['options'])) {
                    $validOptions = array_column($fieldConfig['options'], 'name');
                    if (!in_array($value, $validOptions)) {
                        $errors[] = "The selected {$fieldName} is invalid.";
                    }
                }
                break;
                
            case 'multi_select':
                if (isset($fieldConfig['options']) && is_array($fieldConfig['options'])) {
                    $validOptions = array_column($fieldConfig['options'], 'name');
                    $values = is_array($value) ? $value : [$value];
                    foreach ($values as $val) {
                        if (!in_array($val, $validOptions)) {
                            $errors[] = "One or more selected {$fieldName} values are invalid.";
                            break;
                        }
                    }
                }
                break;
        }
        
        return $errors;
    }

    /**
     * Validate phone number
     * 
     * @param mixed $phone
     * @return bool
     */
    private function isValidPhone(mixed $phone): bool
    {
        if (!is_string($phone)) {
            return false;
        }
        
        // Basic phone validation - can be enhanced based on requirements
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 15;
    }

    /**
     * Validate date
     * 
     * @param mixed $date
     * @return bool
     */
    private function isValidDate(mixed $date): bool
    {
        if (!is_string($date)) {
            return false;
        }
        
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }
        
        return false;
    }
}