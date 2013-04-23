<?php
namespace Paradox\toolbox;
use Paradox\Toolbox;
use Paradox\exceptions\FinderException;
use Paradox\pod\Document;
use Paradox\AModel;

/**
 * Paradox is an elegant Object Document Mananger (ODM) to use with the ArangoDB Document/Graph database server.
 * Paradox requires ArangoDB-PHP to communication with the server, so it needs to be installed and avaliable.
 *
 * Finder
 * Used to find and filter pods (documents) on the server.
 *
 * @version 1.2.3
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class Finder
{
    /**
     * A reference to the toolbox.
     * @var Toolbox
     */
    private $_toolbox;

    /**
     * Instantiates the finder.
     * @param Toolbox $toolbox
     */
    public function __construct(Toolbox $toolbox)
    {
        $this->_toolbox = $toolbox;
    }

    /**
     * Find documents/edges/vertices by filtering, sorting and limiting them. If nothing is found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function find($type, $aql, $params = array(), $placeholder = "doc")
    {
        $query = "FOR $placeholder in @@collection FILTER " . $aql . " return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods($type, $result);
    }

    /**
     * Find documents/edges/vertices by sorting and limiting them. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An optional AQL fragment that will be inserted after the FOR clause.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findAll($type, $aql = "", $params = array(), $placeholder = "doc")
    {
        $query = "FOR $placeholder in @@collection " . $aql . " return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods($type, $result);
    }

    /**
     * Return the first documents/edge/vertex by filter, sorting and limiting a collection. If nothing is found, a null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.name = "john"
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return AModel
     */
    public function findOne($type, $aql, $params = array(), $placeholder = "doc")
    {
        $query = "FOR $placeholder in @@collection FILTER " . $aql . " LIMIT 1 return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);

        $result = $this->_toolbox->getQuery()->getOne($query, $params);

        if (!$result) {
            return null;
        } else {
            $converted = $this->convertToPods($type, array($result));

            return reset($converted);
        }
    }

    /**
     * Returns a random document from a collection.
     * @param  string          $type The collection we want to get the document from.
     * @throws FinderException
     * @return AModel
     */
    public function any($type)
    {
        try {
            $result = $this->_toolbox->getCollectionHandler()->any($type);
            $converted = $this->convertToPods($type, array($result));

            return reset($converted);

        } catch (\triagens\ArangoDb\ServerException $e) {
            throw new FinderException($e->getServerMessage(), $e->getServerCode());
        }
    }

    /**
     * Find documents/edges/vertices near a reference point. If nothing is found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is near the coordinates.
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array    $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  string          $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  integer         $limit       The maximum number of pods to find.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return array
     */
    public function findNear($type, $reference, $aql, $params = array(), $limit = 100, $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //The trick is to find one pod more than requested, and then filter out this pod. This is to work around limitations of NEAR().
            $limit = $limit + 1;

            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter FILTER " . $aql . " return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();
        } else {
            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') FILTER " . $aql . " return $placeholder";
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];
        $params['limit'] = $limit;

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $converted = $this->convertToPods($type, $result, $coordinates);

    }

    /**
     * Find documents/edges/vertices near a reference point. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is near the coordinates.
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array    $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  string          $aql         An optional AQL fragment that will be inserted after the FOR clause.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  integer         $limit       The maximum number of pods to find.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return array
     */
    public function findAllNear($type, $reference, $aql = "", $params = array(), $limit = 100, $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //The trick is to find one pod more than requested, and then filter out this pod. This is to work around limitations of NEAR().
            $limit = $limit + 1;

            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter " . $aql . " return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();
        } else {
            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') " . $aql . " return $placeholder";
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];
        $params['limit'] = $limit;

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $converted = $this->convertToPods($type, $result, $coordinates);

    }

    /**
     * Find one document/edge/vertice near a reference point. If nothing is found, null is returned
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.age > 20 SORT doc.name
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is near the coordinates.
     * @param  string          $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array    $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  string          $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array           $params      An optional associative array containing parameters to bind to the query.
     * @param  string          $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @throws FinderException
     * @return array
     */
    public function findOneNear($type, $reference, $aql, $params = array(), $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //The trick is to find one pod more than requested, and then filter out this pod. This is to work around limitations of NEAR().
            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter FILTER " . $aql . " LIMIT 1 return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();
            $params['limit'] = 2;
        } else {
            $query = "FOR $placeholder in NEAR(@@collection, @latitude, @longitude, @limit, '_paradox_distance_parameter') FILTER " . $aql . " return $placeholder";
            $params['limit'] = 1;
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];

        $result = $this->_toolbox->getQuery()->getOne($query, $params);

        if (!$result) {
            return null;
        } else {
            $converted = $this->convertToPods($type, array($result), $coordinates);

            return reset($converted);
        }
    }

    /**
     * Find documents/edges/vertices within a radius around the reference point. If nothing is found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is within the coordinates.
     * @param  string       $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  float        $radius      The radius in meters.
     * @param  string       $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array        $params      An optional associative array containing parameters to bind to the query.
     * @param  string       $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findWithin($type, $reference, $radius, $aql, $params = array(), $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            //Filter out this pod.
            $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter FILTER " . $aql . " return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();

        } else {
             $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') FILTER " . $aql . " return $placeholder";
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];
        $params['radius'] = $radius;

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $converted = $this->convertToPods($type, $result, $coordinates);
    }

    /**
     * Find documents/edges/vertices within a radius around the reference point. If nothing is found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is within the coordinates.
     * @param  string       $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  float        $radius      The radius in meters.
     * @param  string       $aql         An optional AQL fragment that will be inserted after the FOR claus.
     * @param  array        $params      An optional associative array containing parameters to bind to the query.
     * @param  string       $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function findAllWithin($type, $reference, $radius, $aql = "", $params = array(), $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            //Filter out this pod.
            $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter " . $aql . " return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();

        } else {
            $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') " . $aql . " return $placeholder";
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];
        $params['radius'] = $radius;

        $result = $this->_toolbox->getQuery()->getAll($query, $params);

        if (empty($result)) {
            return array();
        }

        return $converted = $this->convertToPods($type, $result, $coordinates);
    }

    /**
     * Find one document/edge/verticeswithin a radius around the reference point. If nothing is found, null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.age > 20 SORT doc.name
     * If an AModel is passed in, the result array will not include the AModel that was passed in, even though it is within the coordinates.
     * @param  string       $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  AModel|array $reference   The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @param  float        $radius      The radius in meters.
     * @param  string       $aql         An AQL fragment that will be inserted after the FOR claus.
     * @param  array        $params      An optional associative array containing parameters to bind to the query.
     * @param  string       $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return null|AModel
     */
    public function findOneWithin($type, $reference, $radius, $aql, $params = array(), $placeholder = "doc")
    {
        $coordinates = $this->generateReferenceData($reference);

        //If a AModel is passed in, we do not want the pod to be included in the results.
        if ($reference instanceof AModel) {
            //Prevent clashes with the paradox filter id parameter
            $filterParameter = $this->generateParadoxFilterId($params);

            //Filter out this pod.
            $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') FILTER $placeholder._id != @$filterParameter FILTER " . $aql . " LIMIT 1 return $placeholder";

            $params[$filterParameter] = $reference->getPod()->getId();

        } else {
            $query = "FOR $placeholder in WITHIN(@@collection, @latitude, @longitude, @radius, '_paradox_distance_parameter') FILTER " . $aql . " LIMIT 1 return $placeholder";
        }

        $params['@collection'] = $this->getCollectionName($type);
        $params['latitude'] = $coordinates['latitude'];
        $params['longitude'] = $coordinates['longitude'];
        $params['radius'] = $radius;

        $result = $this->_toolbox->getQuery()->getOne($query, $params);

        if (!$result) {
            return null;
        } else {
            $converted = $this->convertToPods($type, array($result), $coordinates);

            return reset($converted);
        }
    }

    /**
     * Search for documents/vertices/edges using a full-text search on an attribute of the documents with filtering. If no results are found, an empty array is returned.
     * By default, the placeholder is "doc", so your query could look something like: doc.age > 20 SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $attribute   The attribute to search on.
     * @param  string $query       The full-text query.
     * @param  string $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function search($type, $attribute, $query, $aql, $params = array(), $placeholder = "doc")
    {
        $aqlStatement = "FOR $placeholder in FULLTEXT(@@collection, @attribute, @query) FILTER " . $aql . " return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);
        $params['attribute'] = $attribute;
        $params['query'] = $query;

        $result = $this->_toolbox->getQuery()->getAll($aqlStatement, $params);

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods($type, $result);
    }

    /**
     * Search for documents/vertices/edges using a full-text search on an attribute of the documents without filtering. If no results are found, an empty array is returned.
     * There is no FILTER keyword here so your query could look something like: SORT doc.name LIMIT 10
     * @param  string $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string $attribute   The attribute to search on.
     * @param  string $query       The full-text query.
     * @param  string $aql         An optional AQL fragment that will be inserted after the FOR clause.
     * @param  array  $params      An optional associative array containing parameters to bind to the query.
     * @param  string $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return array
     */
    public function searchAll($type, $attribute, $query, $aql = "", $params = array(), $placeholder = "doc")
    {
        $aqlStatement = "FOR $placeholder in FULLTEXT(@@collection, @attribute, @query) " . $aql . " return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);
        $params['attribute'] = $attribute;
        $params['query'] = $query;

        $result = $this->_toolbox->getQuery()->getAll($aqlStatement, $params);

        if (empty($result)) {
            return array();
        }

        return $this->convertToPods($type, $result);
    }

    /**
     * Search for one document/vertex/edge using a full-text search on an attribute of the documents with filtering. If no results are found, null is returned.
     * A LIMIT 1 is automatically set, so you do not need to set the LIMIT yourself: doc.name = "john"
     * @param  string      $type        The collection to search in. For graphs, only "vertex" and "edge" are allowed.
     * @param  string      $attribute   The attribute to search on.
     * @param  string      $query       The full-text query.
     * @param  string      $aql         An AQL fragment that will be inserted after the FILTER keyword.
     * @param  array       $params      An optional associative array containing parameters to bind to the query.
     * @param  string      $placeholder Set this to something else if you do not wish to use "doc" to refer to documents in your query.
     * @return null|AModel
     */
    public function searchForOne($type, $attribute, $query, $aql, $params = array(), $placeholder = "doc")
    {
        $aqlStatement = "FOR $placeholder in FULLTEXT(@@collection, @attribute, @query) FILTER " . $aql . " LIMIT 1 return $placeholder";

        $params['@collection'] = $this->getCollectionName($type);
        $params['attribute'] = $attribute;
        $params['query'] = $query;

        $result = $this->_toolbox->getQuery()->getOne($aqlStatement, $params);

        if (!$result) {
            return null;
        } else {
            $converted = $this->convertToPods($type, array($result));

            return reset($converted);
        }
    }

    /**
     * This generates a binding parameter for filtering so that it does not clash with any user defined parameters.
     * @param  array  $parameters An array of binding parameters.
     * @return string
     */
    private function generateParadoxFilterId($parameters)
    {
        $paradoxIdFilter = "paradox_filter_id";
        $parameters = array_keys($parameters);

        while (in_array($paradoxIdFilter, $parameters)) {

            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';

            for ($i = 0; $i < 7; $i++) {
                $paradoxIdFilter .= $characters[rand(0, strlen($characters) - 1)];
            }
        }

        return $paradoxIdFilter;
    }

    /**
     * Convience function to turn a AModel or array of coordinates into an acceptable associative array for find*Near() methods.
     * @param  AModel|array    $reference The reference point. This can be a model or an associative array containing latitude and longitude keys.
     * @throws FinderException
     * @return array
     */
    private function generateReferenceData($reference)
    {
        if ($reference instanceof AModel) {
            $coordinates = $reference->getPod()->getCoordinates();

            if (!$coordinates) {
                throw new FinderException("To use a pod as a reference point, it must have a geo index on its collection and have coordinates assigned.");
            }

            $coordinates['podId'] = $reference->getPod()->getId();
        } elseif (is_array($reference)) {

            if (count($reference) == 2 && array_key_exists('latitude', $reference) && array_key_exists('longitude', $reference)) {
                $coordinates = $reference;
                $coordinates['podId'] = null;
            } else {
                throw new FinderException('$position array passed to findNear() must contain 2 keys: latitude and longitude.');
            }

        } else {
            throw new FinderException('$position must be either an instance of AModel or an array containing the latitude and longitude.');
        }

        return $coordinates;
    }

    /**
     * Converts the an array of associative arrays (each representing a document) received from the server into pods.
     * @param  string $type    The collection type. For graphs, only "vertex" or "edge" is valid.
     * @param  array  $result  The array of documents to convert.
     * @param  array  $geoInfo An associative array of geo distance information we wish to load into the document.
     * @return array
     */
    private function convertToPods($type, $result, array $geoInfo = null)
    {
        $converted = $this->_toolbox->getPodManager()->convertToPods($type, $result);

        foreach ($converted as $model) {

            if ($geoInfo) {
                $model->getPod()->setDistanceInfo($geoInfo['latitude'], $geoInfo['longitude'], $geoInfo['podId']);
            }

            $model->getPod()->setSaved();
        }

        return $converted;
    }

    /**
     * Determines the collection name to use. For graphs, it converts "vertex" and "edge" into the appropriate collection names on the server.
     * @param  string          $type The collection name.
     * @throws FinderException
     * @return string
     */
    private function getCollectionName($type)
    {
        if ($this->_toolbox->isGraph()) {

            if (!$this->_toolbox->getPodManager()->validateType($type)) {
                throw new FinderException("When finding documents in graphs, only the types 'vertex' and 'edge' are allowed.");
            }

            //Is a vertex
            if (strtolower($type) == "vertex") {
                return $this->_toolbox->getVertexCollectionName();

            //Is an edge
            } else {
                return $this->_toolbox->getEdgeCollectionName();
            }

        } else {
            return $type;
        }
    }

}
