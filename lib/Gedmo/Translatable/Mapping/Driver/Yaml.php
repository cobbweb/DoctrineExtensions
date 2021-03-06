<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Translatable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping.Driver
 * @subpackage Yaml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';
    
    /**
     * List of types which are valid for translation,
     * this property is public and you can add some
     * other types in case it needs translation
     * 
     * @var array
     */
    protected $_validTypes = array(
        'string',
        'text'
    );
    
    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata($meta, array $config) {}
    
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $yaml = $this->_loadMappingFile($this->_findMappingFile($meta->name));
        $mapping = $yaml[$meta->name];
        
        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['translation']['entity'])) {
                $translationEntity = $classMapping['translation']['entity'];
                if (!class_exists($translationEntity)) {
                    throw new InvalidMappingException("Translation entity class: {$translationEntity} does not exist.");
                }
                $config['translationClass'] = $translationEntity;
            }
            if (isset($classMapping['translation']['locale'])) {
                $config['locale'] = $classMapping['translation']['locale'];
            } elseif (isset($classMapping['translation']['language'])) {
                $config['locale'] = $classMapping['translation']['language'];
            }
        }
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('translatable', $fieldMapping['gedmo'])) {
                        if (!$this->_isValidField($meta, $field)) {
                            throw new InvalidMappingException("Translatable field - [{$field}] type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['fields'][] = $field;
                    }
                }
            }
        }
    }
    
    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
    
    /**
     * Checks if $field type is valid as Translatable field
     * 
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->_validTypes);
    }
}
