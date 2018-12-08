<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class SchemaOrgHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, array $settings)
    {
        // Build array for JSON and let other components/scripts modify
        $json = $application->Filter(
            'entity_schemaorg_jsonld',
            $this->json($application, $entity, $settings['type'], $settings['properties']),
            array($entity, $settings)
        );
        // Output
        $application->getPlatform()->addHead(
            '<script type="application/ld+json">' . $application->JsonEncode($json) . '</script>',
            'entity_schemaorg_jsonld'
        );
    }
    
    public function json(Application $application, Entity\Type\IEntity $entity, $type, array $properties)
    {
        $json = array(
            '@context' => 'http://schema.org',
            '@type' => $type,
        );
        foreach ($properties as $prop=> $field_name) {
            if (!$field_name
                || (!$field = $application->Entity_Field($entity, $field_name))
                || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                || (!$property = $field_type->fieldSchemaRenderProperty($field, $prop, $entity))
            ) continue;
            
            $json[$prop] = is_array($property) && count($property) === 1 ? $property[0] : $property;
        }
        
        return $json;
    }
    
    public function settingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $types = $application->Filter('entity_schemaorg_types', array(
            'CreativeWork' => array(
                'Book',
                'Game',
                'Movie',
                'Website',
                'Review',
                'Recipe',
                'Comment',
                'Question',
                'Answer',
                'MediaObject' => array(
                    'ImageObject'
                ),
            ),
            'Event',
            'Intangible' => array(
                'Brand',
                'Service',
                'Offer',
            ),
            //'MedicalEntity',
            'Organization' => array(
                'Corporation',
                'LocalBusiness' => array(
                    'FoodEstablishment' => array(
                        'Restaurant',
                    ),
                    'Store',
                ),
                'NGO',
            ),
            'Person',
            'Place',
            'Product'
        ));
        $props = $application->Filter('entity_schemaorg_props', array(
            'name' => [],
            'description' => [],
            'sameAs' => [],
            'url' => [],
            'image' => [],
            'aggregateRating' => array('CreativeWork'. 'Event', 'Organization', 'Place', 'Product', 'Brand', 'Service', 'Offer'),
            'address' => array('Organization', 'Person', 'Place'),
            'telephone' => array('Organization', 'Person', 'Place'),
            'faxNumber' => array('Organization', 'Person', 'Place'),
            'email' => array('Organization', 'Person'),
            'openingHoursSpecification' => array('LocalBusiness'),
            'paymentAccepted' => array('LocalBusiness'),
            'priceRange' => array('LocalBusiness'),
            'geo' => array('Place', 'LocalBusiness'),
            'itemReviewed' => array('Review'),
            'reviewBody' => array('Review'),
            'reviewRating' => array('Review'),
            'genre' => array('CreativeWork'),
            'downvoteCount' => array('Question', 'Comment', 'Answer'),
            'upvoteCount' => array('Question', 'Comment', 'Answer'),
            'parentItem' => array('Comment', 'Answer'),
            'acceptedAnswer' => array('Question'),
            'answerCount' => array('Question'),
            'author' => array('CreativeWork'),
            'commentCount' => array('CreativeWork'),
            'dateCreated' => array('CreativeWork'),
            'dateModified' => array('CreativeWork'),
            'datePublished' => array('CreativeWork'),
            'keywords' => array('CreativeWork'),
            'text' => array('CreativeWork'),
            'startDate' => array('Event'),
            'endDate' => array('Event'),
            'location' => array('Event'),
            'duration' => array('Event', 'Movie'),
            'category' => array('Product', 'Service', 'Offer'),
            'jobTitle' => array('Person'),
            'isbn' => array('Book'),
            'menu' => array('FoodEstablishment'),
            'acceptsReservations' => array('FoodEstablishment'),
            'logo' => array('Brand', 'Organization', 'Place', 'Product'),
            'thumbnail' => array('ImageObject'),
            'contentUrl' => array('MediaObject'),
            'contentSize' => array('MediaObject'),
            'width' => array('MediaObject'),
            'height' => array('MediaObject'),
            'priceRange' => array('LocalBusiness'),
        ));
        $descendants = [];
        $form = array(
            'type' => array(
                '#title' => __('Schema.org JSON-LD', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getSchemaTypeOptions($types, $descendants, array('' => '— ' . __('Select schema type', 'directories') . ' —')),
                '#default_value' => isset($settings['type']) ? $settings['type'] : null,
                '#horizontal' => true,
                '#empty_value' => '',   
            ),
            'properties' => array(
                '#horizontal' => true,
                '#title' => ' ',
                '#element_validate' => [function(Form\Form $form, &$value) use ($parents) {
                    $value = $form->getValue(array_merge($parents, ['type'])) ? array_filter($value) : null;
                }],
                '#states' => array(
                    'invisible' => array(
                        $type_selector = sprintf('select[name="%s[type]"]', $application->Form_FieldName($parents)) => array('value' => ''),
                    )
                ),
            ),
        );

        foreach ($application->Entity_Field($bundle->name) as $field) {
            if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\ISchemable
            ) continue;
            
            foreach ($field_type->fieldSchemaProperties() as $property) {
                if (!isset($props[$property])) continue;
                
                if (!isset($form['properties'][$property])) {
                    $form['properties'][$property] = array(
                        '#type' => 'select',
                        '#title' => $property,
                        '#options' => array(
                            '' => '— ' . __('Select field', 'directories') . ' —',
                        ),
                        '#default_value' => isset($settings['properties'][$property]) ? $settings['properties'][$property] : null,
                        '#horizontal' => true,
                    );
                    if (!empty($props[$property])) {
                        // Make property visible to certain schema types
                        $prop_types = $props[$property];
                        foreach ($props[$property] as $prop_type) {
                            if (!empty($descendants[$prop_type])) {
                                foreach ($descendants[$prop_type] as $descendant) {
                                    $prop_types[] = $descendant;
                                }
                            }
                        }
                        $form['properties'][$property]['#states']['visible'] = array(
                            $type_selector => array('type' => 'one', 'value' => array_unique($prop_types)),
                        );
                    }
                }
                $form['properties'][$property]['#options'][$field->getFieldName()] = $field->getFieldLabel() . ' - ' . $field->getFieldName();
            }
        }
        
        return $form;
    }
    
    protected function _getSchemaTypeOptions($types, array &$descendants, array $options = [], $prefix = '--', array $parents = [])
    {
        foreach ($types as $key => $type) {
            if (is_array($type)) {
                $options[$key] = str_repeat($prefix, count($parents)) . $key;
                foreach ($parents as $parent) {
                    if (!isset($descendants[$parent])) $descendants[$parent] = [];
                    $descendants[$parent][] = $key;
                }
                $_parents = $parents;
                $_parents[] = $key;
                $options = $this->_getSchemaTypeOptions($type, $descendants, $options, $prefix, $_parents);
            } else {
                $options[$type] = str_repeat($prefix, count($parents)) . $type;
                foreach ($parents as $parent) {
                    if (!isset($descendants[$parent])) $descendants[$parent] = [];
                    $descendants[$parent][] = $type;
                }
            }
        }
        
        return $options;
    }
}