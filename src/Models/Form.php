<?php

declare(strict_types=1);

namespace Creeble\Models;

/**
 * Form model representing a Creeble form configuration
 */
class Form extends BaseModel
{
    /**
     * Get form name
     */
    public function getName(): string
    {
        return $this->data['form_name'] ?? $this->data['name'] ?? '';
    }

    /**
     * Get form slug
     */
    public function getSlug(): string
    {
        return $this->data['form_slug'] ?? $this->data['slug'] ?? '';
    }

    /**
     * Check if form is enabled
     */
    public function isEnabled(): bool
    {
        return $this->data['is_form_enabled'] ?? $this->data['enabled'] ?? false;
    }

    /**
     * Get form schema
     */
    public function getSchema(): array
    {
        return $this->data['schema'] ?? [];
    }

    /**
     * Get form fields
     */
    public function getFields(): array
    {
        return $this->getSchema()['properties'] ?? [];
    }

    /**
     * Get form settings
     */
    public function getSettings(): array
    {
        return $this->data['form_settings'] ?? $this->data['settings'] ?? [];
    }

    /**
     * Get success message
     */
    public function getSuccessMessage(): string
    {
        return $this->getSettings()['success_message'] ?? 'Thank you for your submission!';
    }

    /**
     * Get submit button text
     */
    public function getSubmitButtonText(): string
    {
        return $this->getSettings()['submit_button_text'] ?? 'Submit';
    }

    /**
     * Get form description
     */
    public function getDescription(): string
    {
        return $this->getSettings()['description'] ?? '';
    }

    /**
     * Check if captcha is required
     */
    public function requiresCaptcha(): bool
    {
        return $this->getSettings()['requires_captcha'] ?? false;
    }

    /**
     * Get required fields
     * 
     * @return array<string>
     */
    public function getRequiredFields(): array
    {
        $required = [];
        foreach ($this->getFields() as $fieldName => $fieldConfig) {
            if ($fieldConfig['required'] ?? false) {
                $required[] = $fieldName;
            }
        }
        return $required;
    }

    /**
     * Get field by name
     * 
     * @param string $fieldName
     * @return array|null
     */
    public function getField(string $fieldName): ?array
    {
        return $this->getFields()[$fieldName] ?? null;
    }

    /**
     * Check if field exists
     * 
     * @param string $fieldName
     * @return bool
     */
    public function hasField(string $fieldName): bool
    {
        return isset($this->getFields()[$fieldName]);
    }

    /**
     * Get field type
     * 
     * @param string $fieldName
     * @return string|null
     */
    public function getFieldType(string $fieldName): ?string
    {
        $field = $this->getField($fieldName);
        return $field ? ($field['type'] ?? null) : null;
    }

    /**
     * Check if field is required
     * 
     * @param string $fieldName
     * @return bool
     */
    public function isFieldRequired(string $fieldName): bool
    {
        $field = $this->getField($fieldName);
        return $field ? ($field['required'] ?? false) : false;
    }

    /**
     * Get select field options
     * 
     * @param string $fieldName
     * @return array
     */
    public function getFieldOptions(string $fieldName): array
    {
        $field = $this->getField($fieldName);
        if (!$field || !in_array($field['type'] ?? '', ['select', 'multi_select'])) {
            return [];
        }
        return $field['options'] ?? [];
    }

    /**
     * Generate empty form data array
     * 
     * @return array<string, mixed>
     */
    public function getEmptyFormData(): array
    {
        $formData = [];
        foreach ($this->getFields() as $fieldName => $field) {
            $type = $field['type'] ?? 'text';
            $formData[$fieldName] = match($type) {
                'multi_select' => [],
                'checkbox' => false,
                'number' => null,
                default => ''
            };
        }
        return $formData;
    }

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'enabled' => $this->isEnabled(),
            'schema' => $this->getSchema(),
            'settings' => $this->getSettings(),
            'fields' => $this->getFields()
        ];
    }

    /**
     * Create HTML form fields
     * 
     * @param array $values Pre-filled values
     * @param array $errors Validation errors
     * @return string
     */
    public function renderHtmlFields(array $values = [], array $errors = []): string
    {
        $html = '';
        
        foreach ($this->getFields() as $fieldName => $field) {
            $type = $field['type'] ?? 'text';
            $required = $field['required'] ?? false;
            $value = $values[$fieldName] ?? '';
            $error = $errors[$fieldName][0] ?? '';
            
            $html .= '<div class="form-field">';
            $html .= sprintf('<label for="%s">%s%s</label>', 
                htmlspecialchars($fieldName),
                htmlspecialchars($fieldName),
                $required ? ' <span class="required">*</span>' : ''
            );
            
            switch ($type) {
                case 'rich_text':
                case 'long_text':
                    $html .= sprintf('<textarea id="%s" name="%s"%s>%s</textarea>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        $required ? ' required' : '',
                        htmlspecialchars($value)
                    );
                    break;
                    
                case 'select':
                    $html .= sprintf('<select id="%s" name="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        $required ? ' required' : ''
                    );
                    $html .= '<option value="">Choose...</option>';
                    foreach ($this->getFieldOptions($fieldName) as $option) {
                        $html .= sprintf('<option value="%s"%s>%s</option>',
                            htmlspecialchars($option['name'] ?? ''),
                            $value === ($option['name'] ?? '') ? ' selected' : '',
                            htmlspecialchars($option['name'] ?? '')
                        );
                    }
                    $html .= '</select>';
                    break;
                    
                case 'checkbox':
                    $html .= sprintf('<input type="checkbox" id="%s" name="%s" value="1"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        $value ? ' checked' : ''
                    );
                    break;
                    
                case 'email':
                    $html .= sprintf('<input type="email" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
                    break;
                    
                case 'url':
                    $html .= sprintf('<input type="url" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
                    break;
                    
                case 'number':
                    $html .= sprintf('<input type="number" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
                    break;
                    
                case 'phone_number':
                    $html .= sprintf('<input type="tel" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
                    break;
                    
                case 'date':
                    $html .= sprintf('<input type="date" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
                    break;
                    
                default:
                    $html .= sprintf('<input type="text" id="%s" name="%s" value="%s"%s>',
                        htmlspecialchars($fieldName),
                        htmlspecialchars($fieldName),
                        htmlspecialchars($value),
                        $required ? ' required' : ''
                    );
            }
            
            if ($error) {
                $html .= sprintf('<span class="error">%s</span>', htmlspecialchars($error));
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
}