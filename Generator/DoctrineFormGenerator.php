<?php

namespace CPANA\GeneratorBundle\Generator;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a form class based on a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 * @author Massimiliano Arione <garakkio@gmail.com>
 * @author Eugenio Pombi <euxpom@gmail.com>
 */
class DoctrineFormGenerator extends Generator
{
    private $className;
    private $classPath;

    public function getClassName()
    {
        return $this->className;
    }

    public function getClassPath()
    {
        return $this->classPath;
    }

    /**
     * Generates the entity form class if it does not exist.
     *
     * @param BundleInterface   $bundle     The origin bundle
     * @param BundleInterface   $destBundle The destination bundle
     * @param string            $entity     The entity relative class name
     * @param ClassMetadataInfo $metadata   The entity metadata class
     *
     * @throws \RuntimeException
     */
    public function generate(BundleInterface $bundle, BundleInterface $destBundle, $entity, ClassMetadataInfo $metadata)
    {
        $parts       = explode('\\', $entity);
        $entityClass = array_pop($parts);

        $this->className = $entityClass.'Type';
        $dirPath         = $destBundle->getPath().'/Form/Type';
        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $entity).'Type.php';

        if (file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The form generator does not support entity classes with multiple primary keys.');
        }

        $parts = explode('\\', $entity);
        array_pop($parts);

        $this->renderFile('form/FormType.php.twig', $this->classPath, array(
            'fields'           => $this->getFieldsFromMetadata($metadata),
            'namespace'        => $destBundle->getNamespace(),
            'bundle_namespace' => $bundle->getNamespace(),
            'entity_namespace' => implode('\\', $parts),
            'entity_class'     => $entityClass,
            'bundle'           => $bundle->getName(),
            'form_class'       => $this->className,
            'form_type_name'   => strtolower(str_replace('\\', '_', $bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$this->className),
        ));
    }

    /**
     * Generates the entity form class if it does not exist.
     *
     * @param  BundleInterface   $bundle     The origin bundle
     * @param  BundleInterface   $destBundle The bundle in which to create the class
     * @param  string            $entity     The entity relative class name
     * @param  ClassMetadataInfo $metadata   The entity metadata class
     * @throws \RuntimeException
     */
    public function generateFilter(BundleInterface $bundle, BundleInterface $destBundle, $entity, ClassMetadataInfo $metadata)
    {
        $parts       = explode('\\', $entity);
        $entityClass = array_pop($parts);

        $this->className = $entityClass.'FilterType';
        $dirPath         = $destBundle->getPath().'/Form/Type';
        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $entity).'FilterType.php';

        if (file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s filter form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The form generator does not support entity classes with multiple primary keys.');
        }

        $parts = explode('\\', $entity);
        array_pop($parts);

        $this->renderFile('filter/FormFilterType.php.twig', $this->classPath, array(
            'bundle'           => $bundle->getName(),
            'fields'           => $this->getFieldsFromMetadata($metadata),
            'namespace'        => $destBundle->getNamespace(),
            'bundle_namespace' => $bundle->getNamespace(),
            'entity_namespace' => implode('\\', $parts),
            'entity_class'     => $entityClass,
            'form_class'       => $this->className,
            'form_type_name'   => strtolower(str_replace('\\', '_', $bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$this->className),
        ));
    }

    /**
     * Returns an array of fields. Fields can be both column fields and
     * association fields.
     *
     * @param  ClassMetadataInfo $metadata
     * @return array             $fields
     */
    private function getFieldsFromMetadata(ClassMetadataInfo $metadata)
    {
        $fields = (array) $metadata->fieldMappings;
        
        foreach ($metadata->associationMappings as $fieldName => $relation) {
            if ($relation['type'] !== ClassMetadataInfo::ONE_TO_MANY) {
                if ($relation['type'] === ClassMetadataInfo::MANY_TO_MANY) {
                    $fields[$fieldName] = array('type' => 'relation_many', 'entity' => $relation['targetEntity']);
                } else {
                    $fields[$fieldName] = array('type' => 'relation', 'entity' => $relation['targetEntity']);
                }
            }
        }
         
         
        /*
        $assoc=(array)$metadata->associationMappings ;
        foreach($assoc as $key => $value){
            if(1==$assoc[$key]['isOwningSide']){
                $fields[$assoc[$key]['fieldName']] = array('fieldName' => $assoc[$key]['fieldName'], 'entity' => $assoc[$key]['targetEntity']);
            }
        }
         
         */

        return $fields;
    }
}
