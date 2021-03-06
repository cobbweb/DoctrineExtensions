<?php

namespace Gedmo\Timestampable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Timestampable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable.Mapping.Driver
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
     * List of types which are valid for timestamp
     * 
     * @var array
     */
    private $_validTypes = array(
        'date',
        'time',
        'datetime',
        'timestamp'
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
        
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['timestampable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['timestampable'];
                    if (!$this->_isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'date', 'datetime' or 'time' in class - {$meta->name}");
                    }
                    if (!isset($mappingProperty['on']) || !in_array($mappingProperty['on'], array('update', 'create', 'change'))) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                    }
                    
                    if ($mappingProperty['on'] == 'change') {
                        if (!isset($mappingProperty['field']) || !isset($mappingProperty['value'])) {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field and value must be set on [change] trigger in class - {$meta->name}");
                        }
                        $field = array(
                            'field' => $field,
                            'trackedField' => $mappingProperty['field'],
                            'value' => $mappingProperty['value'] 
                        );
                    }
                    $config[$mappingProperty['on']][] = $field;
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
     * Checks if $field type is valid
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
