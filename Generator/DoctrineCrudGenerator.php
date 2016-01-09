<?php

namespace CPANA\GeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;

/**
 * Generates a CRUD controller.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Leonardo Proietti <leonardo.proietti@gmail.com>
 * @author Massimiliano Arione <garakkio@gmail.com>
 * @author Eugenio Pombi <euxpom@gmail.com>
 */
class DoctrineCrudGenerator extends Generator
{
    protected $filesystem;
    protected $routePrefix;
    protected $routeNamePrefix;
    protected $bundle;
    protected $destBundle;
    protected $entity;
    protected $metadata;
    protected $format;
    protected $actions;
    protected $layout;
    protected $bodyBlock;
    protected $usePaginator;
    protected $theme;
    protected $filterTemplate;
    protected $withFilter;
    protected $withSort;
    //----- fields keeping informations about associated entities in BIDIRECTONAL relations --------
    protected $entityAssocProperty=array(); // is the name of the property(ArrayCollection) holding objects from the associatied class ( and not the name of the actual assoc. class)
    protected $fieldsOfAssocEntity=array(); //an array of the fields(properties) from the associated class
    protected $entityAssocClassName=array(); // name of the associated class, like AppBundle\Entity\MyEntity
    //----- fields keeping info about properties which are owning sides (the many side from a one-to-many)------
    protected $propertiesOwningSide= array();
    /**
     * Constructor.
     *
     * @param Filesystem $filesystem A Filesystem instance
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem  = $filesystem;
    }

    /**
     * Generate the CRUD controller.
     *
     * @param BundleInterface   $bundle           A bundle object
     * @param BundleInterface   $destBundle       The destination bundle object
     * @param string            $entity           The entity relative class name
     * @param ClassMetadataInfo $metadata         The entity class metadata
     * @param string            $format           The configuration format (xml, yaml, annotation)
     * @param string            $routePrefix      The route name prefix
     * @param bool              $needWriteActions Wether or not to generate write actions
     * @param bool              $forceOverwrite   Wether to overwrate the controller file if it already exists
     * @param string            $layout           The layout (default: "TwigBundle::layout.html.twig")
     * @param string            $bodyBlock        The name of body block in layout (default: "body")
     * @param bool              $usePaginator     Wether or not to use paginator
     * @param string            $theme            Possible theme for forms
     * @param bool              $withFilter       Wether or not to use filters
     * @param bool              $withSort         Wether or not to use sorting
     *
     * @throws \RuntimeException
     */
    public function generate(BundleInterface $bundle, BundleInterface $destBundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite, $layout, $bodyBlock, $usePaginator = false, $theme = null, $withFilter = false, $withSort = false)
    {
        $this->routePrefix = $routePrefix;
        $this->routeNamePrefix = str_replace('/', '_', $routePrefix);
        $this->actions = $needWriteActions ? array('index', 'show', 'new', 'edit', 'delete') : array('index', 'show');

        if ($withSort) {
            $this->actions[] = 'sort';
        }

        if ($withFilter) {
            $this->actions[] = 'filter';
            $this->filterTemplate = '';
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The CRUD generator does not support entity classes with multiple primary keys.');
        }

        if (!in_array('id', $metadata->identifier)) {
            throw new \RuntimeException('The CRUD generator expects the entity object has a primary key field named "id" with a getId() method.');
        }

        $this->entity       = $entity;
        $this->bundle       = $bundle;
        $this->destBundle   = $destBundle;
        $this->layout       = $layout;
        $this->bodyBlock    = $bodyBlock;
        $this->metadata     = $metadata;
        $this->usePaginator = $usePaginator;
        $this->withFilter   = $withFilter;
        $this->withSort     = $withSort;
        $this->theme        = $theme;
        $this->setFormat($format);
        $this->getAssociatedEntities();
        
        
        $this->generateControllerClass($forceOverwrite);

        // TODO for now we do strtolower, but we need a CamelCase to snake_case conversion
        $dir = sprintf('%s/Resources/views/%s', $this->destBundle->getPath(), strtolower(str_replace('\\', '/', $this->entity)));

        if (!file_exists($dir)) {
            $this->filesystem->mkdir($dir, 0777);
        }

        $this->generateIndexView($dir);

        if (in_array('show', $this->actions)) {
            $this->generateShowView($dir);
        }

        if (in_array('new', $this->actions)) {
            $this->generateNewView($dir);
        }

        if (in_array('edit', $this->actions)) {
            $this->generateEditView($dir);
        }

        if (in_array('filter', $this->actions)) {
            $this->generateFilterView($dir);
        }

        $this->generateTestClass();
        $this->generateConfiguration();
    }
    
    /**
     * Sets the variables holding information about associated entities in bidirectional relations.
     *
     * @author Cristian Pana
     */
    protected function getAssociatedEntities()
    {
        
		$keys= array();
		
		//--- verify if there is any property representing an association (one-to-many bidirectional etc)
        if (!empty(array_keys($this->metadata->associationMappings)))   
        {  
            $assoc = $this->metadata->associationMappings;
         
            foreach($assoc as $key => $value){
                if( 0==$assoc[$key]['isOwningSide']) {
                    $keys[] = $assoc[$key]['fieldName'];
                    $this->entityAssocClassName[$assoc[$key]['fieldName']] = $assoc[$key]['targetEntity']; 
                }
            }
            if (!empty($keys)) {           //it is possible that the entity has association but if they are owning sides we do not want to add them,  
                $this->entityAssocProperty=(array)$keys;   
          
                global $kernel;;
                $factory = new DisconnectedMetadataFactory($kernel->getContainer()->get('doctrine'));
				
                foreach($this->entityAssocClassName as $fieldName => $className ){
                    //$metadata_assoc = $factory->getClassMetadata($this->entityAssocClassName[$value])->getMetadata();
                    $metadata_assoc = $factory->getClassMetadata($className)->getMetadata();
                    $this->fieldsOfAssocEntity[$fieldName] =$metadata_assoc[0]->fieldMappings;
                }
            }
        }
    }
                
    
    /**
     * Sets the configuration format.
     *
     * @param string $format The configuration format
     */
    private function setFormat($format)
    {
        switch ($format) {
            case 'yml':
            case 'xml':
            case 'php':
            case 'annotation':
                $this->format = $format;
                break;
            default:
                $this->format = 'yml';
                break;
        }
    }

    /**
     * Generates the routing configuration.
     *
     */
    protected function generateConfiguration()
    {
        if (!in_array($this->format, array('yml', 'xml', 'php'))) {
            return;
        }

        $target = sprintf(
            '%s/Resources/config/routing/%s.%s',
            $this->bundle->getPath(),
            strtolower(str_replace('\\', '_', $this->entity)),
            $this->format
        );

        $this->renderFile('crud/config/routing.'.$this->format.'.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
        ));
    }

    /**
     * Generates the controller class only.
     *
     */
    protected function generateControllerClass($forceOverwrite)
    {
        $dir = $this->destBundle->getPath();

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $target = sprintf(
            '%s/Controller/%s/%sController.php',
            $dir,
            str_replace('\\', '/', $entityNamespace),
            $entityClass
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the controller as it already exists.');
        }

        $this->renderFile('crud/controller.php.twig', $target, array(
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle'            => $this->bundle->getName(),
            'dest_bundle'       => $this->destBundle->getName(),
            'entity'            => $this->entity,
            'entity_class'      => $entityClass,
            'namespace'         => $this->destBundle->getNamespace(),
            'bundle_namespace'  => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'format'            => $this->format,
            'usePaginator'      => $this->usePaginator,
            'withFilter'        => $this->withFilter,
            'withSort'          => $this->withSort,
            'propertiesOwningSide' => $this->propertiesOwningSide,
        ));
    }

    /**
     * Generates the functional test class only.
     *
     */
    protected function generateTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $dir    = $this->destBundle->getPath() .'/Tests/Controller';
        $target = $dir .'/'. str_replace('\\', '/', $entityNamespace).'/'. $entityClass .'ControllerTest.php';

        $this->renderFile('crud/tests/test.php.twig', $target, array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'bundle'            => $this->bundle->getName(),
            'entity_class'      => $entityClass,
            'namespace'         => $this->bundle->getNamespace(),
            'entity_namespace'  => $entityNamespace,
            'actions'           => $this->actions,
            'form_type_name'    => strtolower($entityClass),
            'withFilter'        => $this->withFilter,
            'withSort'          => $this->withSort,
            'fields'            => $this->metadata->fieldMappings,
        ));
    }

    /**
     * Generates the index.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateIndexView($dir)
    {
        $this->renderFile('crud/views/index.html.twig.twig', $dir.'/index.html.twig', array(
            'bundle'            => $this->bundle->getName(),
            'dest_bundle'       => $this->destBundle->getName(),
            'entity'            => $this->entity,
            'fields'            => $this->metadata->fieldMappings,
            'actions'           => $this->actions,
            'record_actions'    => $this->getRecordActions(),
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'layout'            => $this->layout,
            'bodyBlock'         => $this->bodyBlock,
            'usePaginator'      => $this->usePaginator,
            'withFilter'        => $this->withFilter,
            'withSort'          => $this->withSort,
            'bundle'            => $this->bundle->getName(),
        ));
    }

    /**
     * Generates the show.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    private function generateShowView($dir)
    {

        $this->renderFile('crud/views/show.html.twig.twig', $dir.'/show.html.twig', array(
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'fields'            => $this->metadata->fieldMappings,
            'entity_assoc_array'      => (array)$this->entityAssocProperty,  // added by CPANA
            'fields_assoc_array'      => (array)$this->fieldsOfAssocEntity,  // added by CPANA
            //'entity_assoc_class'=> $this->entityAssocClassName,
            'actions'           => $this->actions,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'layout'            => $this->layout,
            'bodyBlock'         => $this->bodyBlock,
        ));
    }

    /**
     * Generates the new.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    private function generateNewView($dir)
    {
        $this->renderFile('crud/views/new.html.twig.twig', $dir.'/new.html.twig', array(
            'bundle'            => $this->bundle->getName(),
            'entity'            => $this->entity,
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'actions'           => $this->actions,
            'layout'            => $this->layout,
            'bodyBlock'         => $this->bodyBlock,
            'theme'             => $this->theme,
        ));
    }

    /**
     * Generates the edit.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateEditView($dir)
    {
        $this->renderFile('crud/views/edit.html.twig.twig', $dir.'/edit.html.twig', array(
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'bundle'            => $this->bundle->getName(),
            'actions'           => $this->actions,
            'layout'            => $this->layout,
            'bodyBlock'         => $this->bodyBlock,
            'theme'             => $this->theme,
        ));
    }

    /**
     * Generates the filter.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    private function generateFilterView($dir)
    {
        $this->renderFile('crud/views/filter.html.twig.twig', $dir.'/filter.html.twig', array(
            'bundle'            => $this->bundle->getName(),
            'route_prefix'      => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity'            => $this->entity,
            'actions'           => $this->actions,
            'layout'            => $this->layout,
            'bodyBlock'         => $this->bodyBlock,
            'theme'             => $this->theme,
        ));
    }

    /**
     * Returns an array of record actions to generate (edit, show).
     *
     * @return array
     */
    private function getRecordActions()
    {
        return array_filter($this->actions, function ($item) {
            return in_array($item, array('show', 'edit'));
        });
    }
}
