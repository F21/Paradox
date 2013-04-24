<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\CollectionManagerException;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Collection manager
 * Manages collections, for example, creating and deleting collections and indices.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class CollectionManager
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * A small cache to store the index into to prevent too many trips to the server.
     * @var array()
     */
    private $_indexInfoCache = array();

    /**
     * Instantiates the finder.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Create a collection.
     * @param  string          $name The name of the collection.
     * @throws CollectionManagerException
     */
    public function createCollection($name)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->create($name);
        } catch (\Exception $e) {
        	$normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Delete a collection.
     * @param  string          $name The name of the collection.
     * @throws CollectionManagerException
     */
    public function deleteCollection($name)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->delete($name);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Renames a collection.
     * @param  string          $collection The collection we wish to rename.
     * @param  string          $newName    The new name of the collection.
     * @throws CollectionManagerException
     */
    public function renameCollection($collection, $newName)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->rename($collection, $newName);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Deletes all the documents inside the collection, but leaves the indexes and metadata intact.
     * @param  string          $collection The name of the collection.
     * @throws CollectionManagerException
     */
    public function wipe($collection)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->truncate($collection);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get information about a collection.
     * @param  string          $collection The name of the collection.
     * @throws CollectionManagerException
     * @return array
     */
    public function getCollectionInfo($collection)
    {
        try {
            $collection = $this->_toolbox->getCollectionHandler()->getProperties($collection);

            $result = $collection->getAll();

            switch ($result['type']) {
                case 2:
                    $result['type'] = "documents";
                    break;

                case 3:
                    $result['type'] = "edges";
                    break;
            }

            return $result;

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get statistics from a collection.
     * @param  string          $collection The name of the collection.
     * @throws CollectionManagerException
     * @return array
     */
    public function getCollectionStatistics($collection)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->getFigures($collection);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Counts the number of documents in a collection.
     * @param  string          $collection The collection.
     * @throws CollectionManagerException
     * @return integer
     */
    public function count($collection)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->count($collection);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * List all the collections on the server.
     * @param  boolean         $excludeSystem Whether we want to include system collections in the list or not.
     * @param  boolean         $includeInfo   Whether we want to include information about each collection. If false, only a list of collection names will be returned.
     * @throws CollectionManagerException
     * @return array
     */
    public function listCollections($excludeSystem = true, $includeInfo = false)
    {
        try {
            $result = $this->_toolbox->getCollectionHandler()->getAllCollections(array('excludeSystem' => (bool) $excludeSystem));

            if (empty($result)) {
                return array();
            }

            if (!$includeInfo) {
                return array_keys($result);
            } else {
                return $result;
            }
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Load a collection on the server.
     * @param  string          $collection The name of the collection.
     * @throws CollectionManagerException
     */
    public function loadCollection($collection)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->load($collection);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Unload a collection on the server.
     * @param  string          $collection The name of the collection.
     * @throws CollectionManagerException
     */
    public function unloadCollection($collection)
    {
        try {
            return $this->_toolbox->getCollectionHandler()->unload($collection);
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Create a cap constraint on a collection.
     * @param string $collection The name of the collection.
     * @param int    $size       The size of the cap constraint.
     * @link http://www.arangodb.org/manuals/current/IndexCapHttp.html
     * @throws CollectionManagerException
     * @return int                        Id of the index created.
     */
    public function createCapConstraint($collection, $size)
    {
        try {
            $result = $this->_toolbox->getCollectionHandler()->createCapConstraint($collection, $size);

            return $result['id'];
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Create a geo index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     An array of 2 fields representing the latitude and longitude, or 1 field representing a list attribute.
     * @param boolean      $geoJson    Whether to use geoJson or not.
     * @param boolean      $constraint Whether this is a constraint or not.
     * @param boolean      $ignoreNull Whether to ignore null.
     * @link http://www.arangodb.org/manuals/current/IndexGeoHttp.html
     * @throws CollectionManagerException
     * @return int                        Id of the index created.
     */
    public function createGeoIndex($collection, $fields, $geoJson = null, $constraint = null, $ignoreNull = null)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        try {
            $result = $this->_toolbox->getCollectionHandler()->createGeoIndex($collection, $fields, $geoJson, $constraint, $ignoreNull);

            return $result['id'];
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Create a hash index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     The array of fields or a string representing 1 field.
     * @param boolean      $unique     Whether the values in the index should be unique or not.
     * @link http://www.arangodb.org/manuals/current/IndexHashHttp.html
     * @throws CollectionManagerException
     * @return int                        Id of the index created.
     */
    public function createHashIndex($collection, $fields, $unique = null)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        try {
            $result = $this->_toolbox->getCollectionHandler()->createHashIndex($collection, $fields, $unique);

            return $result['id'];
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Create a fulltext index on a collection.
     * @param string $collection The name of the collection.
     * @param string $field      The field to index. Fulltext indices can currently only index one field.
     * @param int    $minLength  The minimum length of words to index.
     * @link http://www.arangodb.org/manuals/current/IndexFulltextHttp.html
     * @throws CollectionManagerException
     * @return int                        Id of the index created.
     */
    public function createFulltextIndex($collection, $fields, $minLength = null)
    {
        if (!is_string($fields)) {
            throw new CollectionManagerException("Fulltext indices can currently only index one field.");
        }

        $fields = array($fields);

        try {
            $result = $this->_toolbox->getCollectionHandler()->createFulltextIndex($collection, $fields, $minLength);

            return $result['id'];
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Create a skip-list index on a collection.
     * @param string       $collection The name of the collection.
     * @param array|string $fields     The array of fields or a string representing 1 field.
     * @param bool         $unique     Whether the index is unique or not.
     * @link http://www.arangodb.org/manuals/current/IndexSkiplistHttp.html
     * @throws CollectionManagerException
     * @return int                        Id of the index created.
     */
    public function createSkipListIndex($collection, $fields, $unique = null)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        try {
            $result = $this->_toolbox->getCollectionHandler()->createSkipListIndex($collection, $fields, $unique);

            return $result['id'];
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Delete an index.
     * @param  string                     $collection The collection the index is on.
     * @param  string                     $indexId    The id of the index we want to delete.
     * @throws CollectionManagerException
     * @return boolean
     */
    public function deleteIndex($collection, $indexId)
    {
        try {
            $this->_toolbox->getCollectionHandler()->dropIndex($collection . '/' . $indexId);

            //Invalidate the cached index info for this collection
            unset($this->_indexInfoCache[$collection]);

            return true;
        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get information about an index. Returns null if the index does not exist and returns an array of index information if it does.
     * @param  string     $collection The name of the collection.
     * @param  string     $indexId    The id of the index.
     * @return null|array
     */
    public function getIndexInfo($collection, $indexId)
    {
        $result = $this->listIndices($collection);

        return isset($result[$collection . '/' . $indexId]) ? $result[$collection . '/' . $indexId] : null;
    }

    /**
     * List the indices on a collection.
     * @param  string                     $collection  The name of the collection.
     * @param  boolean                    $includeInfo Whether we want information on each index. If false, only an array of index ids will be returned.
     * @throws CollectionManagerException
     * @return array
     */
    public function listIndices($collection, $includeInfo = false)
    {

        if (isset($this->_indexInfoCache[$collection])) {
            return $this->_indexInfoCache[$collection];
        }

        try {
            $result = $this->_toolbox->getCollectionHandler()->getIndexes($collection);
            $this->_indexInfoCache[$collection] = $result['identifiers'];

            if ($includeInfo) {
                return $this->_indexInfoCache[$collection];
            } else {
                return array_keys($this->_indexInfoCache[$collection]);
            }

        } catch (\Exception $e) {
            $normalised = $this->_toolbox->normaliseDriverExceptions($e);
            throw new CollectionManagerException($normalised['message'], $normalised['code']);
        }
    }

    /**
     * Get the fields that are used to store geo coordinates for the first index for this collection.
     * @param  string          $collection The name of the collection
     * @throws FinderException
     * @return array|null
     */
    public function getGeoFieldsForAQL($collection)
    {
        $indices = $this->_toolbox->getCollectionManager()->listIndices($collection, true);

        foreach ($indices as $index => $info) {

            //If this is the first geo index we encounter
            if ($info['type'] == "geo1" || $info['type'] == "geo2") {
                return $info['fields'];
            }
        }

        return null;
    }
}
