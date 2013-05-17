<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\Finder;
use Paradox\AModel;

/**
 * Tests for the finder.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class FinderTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'FinderTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'FinderTestGraph';

    /**
     * Stores the finder.
     * @var Finder
     */
    protected $finder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $client = $this->getClient();

        //Try to delete any leftovers
        try {
            $client->deleteCollection($this->collectionName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        $client->createCollection($this->collectionName);
        $client->createGeoIndex($this->collectionName, 'geofield');
        $client->createFulltextIndex($this->collectionName, 'bio');

        //Setup the test data
        $document1 = $client->dispense($this->collectionName);
        $document1->set('name', 'Horacio Manuel Cartes Jara');
        $document1->set('bio', "Cartes' father was the owner of a Cessna aircraft franchise holding company and Horacio Cartes studied aeronautical engineering in the United States. At the age of 19, he started a currency exchange business which grew into the Banco Amambay. Over the following years, Cartes bought or helped establish 25 companies including Tabesa, the country's biggest cigarette manufacturer, and a major fruit juice bottling company.");
        $document1->set('geofield', array(48.1, 48.1));
        $client->store($document1);

        $document2 = $client->dispense($this->collectionName);
        $document2->set('name', 'Tsegaye Kebede');
        $document2->set('bio', "In the 2009 season he established himself as one of Ethiopia's top athletes: he came second in the London Marathon and at his first World Championships in Athletics he took the bronze medal in the marathon. He retained his Fukuoka Marathon title at the end of 2009, running the fastest ever marathon race in Japan. He won the 2010 London Marathon, his first World Marathon Major and the 2013 London Marathon.");
        $document2->set('geofield', array(48.1, 48.1));
        $client->store($document2);

        $document3 = $client->dispense($this->collectionName);
        $document3->set('name', 'Giorgio Napolitano');
        $document3->set('bio', "Giorgio Napolitano was born in Naples, Italy. In 1942, he matriculated at the University of Naples Federico II. He adhered to the local University Fascist Youth, where he met his core group of friends, who shared his membership in the Italian fascism. As he would later state, the group was in fact a true breeding ground of anti-fascist intellectual energies, disguised and to a certain extent tolerated.");
        $document3->set('geofield', array(48.2, 48.2));
        $client->store($document3);

        $document4 = $client->dispense($this->collectionName);
        $document4->set('name', 'Priscah Jeptoo');
        $document4->set('bio', "She began competing at top level competitions in 2008 and made the top ten women at the Saint Silvester Road Race that year. Her 2009 began with two wins in Portugal, at the Douro-Tal Half Marathon and then the Corrida Festas Cidade do Porto 15K race. These preceded a course record-breaking run at the Porto Marathon in November, as she recorded a time of 2:30:40 hours for her debut effort.");
        $document4->set('geofield', array(50, 50));
        $client->store($document4);

        $client->createGraph($this->graphName);

        $this->finder = $this->getFinder();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $client = $this->getClient();

        try {
            $client->deleteCollection($this->collectionName);
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteCollection($this->collectionName . 'Empty');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteCollection($this->collectionName . 'NoIndices');
        } catch (\Exception $e) {
            //Ignore any errors
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore any errors
        }
    }

    /**
     * Convinence function to get the finder
     */
    protected function getFinder($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox()->getFinder();
    }

    /**
     * @covers Paradox\toolbox\Finder::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new Finder($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'Finder constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\Finder::find
     */
    public function testFind()
    {
        $found = $this->finder->find($this->collectionName, "myplaceholder.name == @name", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');

        $this->assertInternalType('array', $found, 'Returned result is not an array');
        $this->assertCount(1, $found, "The number of documents found is not 1");

        $foundDocument = reset($found);
        $this->assertInstanceOf('Paradox\AModel', $foundDocument, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $foundDocument->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::find
     */
    public function testFindInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    	
    	$client->begin();
    	$finder->find($this->collectionName, "myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');
    	$client->registerResult('noResult');
    	
    	$finder->find($this->collectionName, "myplaceholder.name IN ['Giorgio Napolitano', 'Tsegaye Kebede']", array(), 'myplaceholder');
    	$client->registerResult('found');
    	
    	$result = $client->commit();
    	
    	$this->assertEmpty($result['noResult'], "There should be no results for the first query");
    	
    	$found = $result['found'];
    	$this->assertCount(2, $found, "There should be 2 results for the second query");
    	
    	foreach ($found as $key => $document) {
    		$this->assertInternalType('string', $key, "The id of the found document should be a string");
    		$this->assertInstanceOf('Paradox\AModel', $document, 'The found document does not have the type Paradox\AModel');
    		$this->assertContains($document->get('name'), array('Giorgio Napolitano', 'Tsegaye Kebede'), "The found document has an unexpected name.");
    	}
    }

    /**
     * @covers Paradox\toolbox\Finder::find
     */
    public function testFindWithInvalidAQL()
    {
        try {
            $found = $this->finder->find($this->collectionName, "FILTER myplaceholder.name == @name", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying the finder with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::find
     */
    public function testFindWithNoResults()
    {
        $found = $this->finder->find($this->collectionName, "myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');

        $this->assertInternalType('array', $found, 'Returned result is not an array');
        $this->assertEmpty($found, "The number of documents found is not 0");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAll
     */
    public function testFindAll()
    {
        $found = $this->finder->findAll($this->collectionName, "FILTER myplaceholder.name == @name LIMIT 1", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');

        $this->assertInternalType('array', $found, 'Returned result is not an array');
        $this->assertCount(1, $found, "The number of documents found is not 1");

        $foundDocument = reset($found);
        $this->assertInstanceOf('Paradox\AModel', $foundDocument, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $foundDocument->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findAll
     */
    public function testFindAllInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    	 
    	$client->begin();
    	$finder->findAll($this->collectionName, "FILTER myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');
    	$client->registerResult('noResult');
    	 
    	$finder->findAll($this->collectionName, "FILTER myplaceholder.name IN ['Giorgio Napolitano', 'Tsegaye Kebede']", array(), 'myplaceholder');
    	$client->registerResult('found');
    	 
    	$result = $client->commit();
    	 
    	$this->assertEmpty($result['noResult'], "There should be no results for the first query");
    	 
    	$found = $result['found'];
    	$this->assertCount(2, $found, "There should be 2 results for the second query");
    	 
    	foreach ($found as $key => $document) {
    		$this->assertInternalType('string', $key, "The id of the found document should be a string");
    		$this->assertInstanceOf('Paradox\AModel', $document, 'The found document does not have the type Paradox\AModel');
    		$this->assertContains($document->get('name'), array('Giorgio Napolitano', 'Tsegaye Kebede'), "The found document has an unexpected name.");
    	}
    }

    /**
     * @covers Paradox\toolbox\Finder::findAll
     */
    public function testFindAllWithInvalidAQL()
    {
        try {
            $found = $this->finder->findAll($this->collectionName, "myplaceholder.name == @name", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying the finder with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAll
     */
    public function testFindAllWithNoResults()
    {
        $found = $this->finder->findAll($this->collectionName, "FILTER myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');

        $this->assertInternalType('array', $found, 'Returned result is not an array');
        $this->assertEmpty($found, "The number of documents found is not 0");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOne
     */
    public function testFindOne()
    {
        $found = $this->finder->findOne($this->collectionName, "myplaceholder.name == @name", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');

        $this->assertInstanceOf('Paradox\AModel', $found, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Horacio Manuel Cartes Jara', $found->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findOne
     */
    public function testFindOneInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	$client->begin();
    	$finder->findOne($this->collectionName, "myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');
    	$client->registerResult('noResult');
    
    	$finder->findOne($this->collectionName, "myplaceholder.name IN ['Giorgio Napolitano']", array(), 'myplaceholder');
    	$client->registerResult('found');
    
    	$result = $client->commit();
    
    	$this->assertNull($result['noResult'], "There should be no results for the first query");
    
    	$found = $result['found'];
    	
    	$this->assertInstanceOf('Paradox\AModel', $found, 'The found document does not have the type Paradox\AModel');
    	$this->assertEquals('Giorgio Napolitano', $found->get('name'), "The found document's name does not match.");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOne
     */
    public function testFindOneWithInvalidAQL()
    {
        try {
            $found = $this->finder->findOne($this->collectionName, "FILTER myplaceholder.name == @name LIMIT 1", array('name' => 'Horacio Manuel Cartes Jara'), 'myplaceholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying the finder with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOne
     */
    public function testFindOneWithNoResults()
    {
        $found = $this->finder->findOne($this->collectionName, "myplaceholder.name == @name", array('name' => 'ANameThatDoesNotExist'), 'myplaceholder');

        $this->assertNull($found, 'Returned result is not null');
    }

    /**
     * @covers Paradox\toolbox\Finder::any
     */
    public function testAny()
    {
        for ($i = 0; $i < 10; $i++) {
            $any = $this->finder->any($this->collectionName);
            $this->assertInstanceOf('Paradox\AModel', $any);
            $this->assertContains($any->get('name'), array('Horacio Manuel Cartes Jara', 'Tsegaye Kebede', 'Giorgio Napolitano', 'Priscah Jeptoo'));
        }
    }

    /**
     * @covers Paradox\toolbox\Finder::any
     */
    public function testAnyInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    	
    	$id = $client->createCollection($this->collectionName . 'Empty');
    
    	$client->begin();
    	$finder->any($this->collectionName);
    	$client->registerResult('any');
    	
    	$finder->any($this->collectionName . 'Empty');
    	$client->registerResult('none');
    	$result = $client->commit();
    
    	$this->assertInstanceOf('Paradox\AModel', $result['any'], 'The found document should be of the type Paradox\AModel');
    
    	$this->assertNull($result['none'], "The second query should be null");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::any
     */
    public function testAnyOnInvalidCollection()
    {
        try {
            $any = $this->finder->any('CollectionThatDoesNotExist');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for any document on an invalid collection did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::any
     */
    public function testAnyOnEmptyCollection()
    {
        $id = $this->getClient()->createCollection($this->collectionName . 'Empty');
        $any = $this->finder->any($this->collectionName . 'Empty');

        $this->assertNull($any, 'Using any() on an empty array should return null');
    }

    /**
     * @covers Paradox\toolbox\Finder::findNear
     */
    public function testFindNearWithCoordinates()
    {
        $results = $this->finder->findNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");

        $this->assertInternalType('array', $results, "Returned result set should be an array");
        $this->assertCount(1, $results, "Number of returned results should be 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findNear
     */
    public function testFindNearInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    	
    	$client->begin();
    	$finder->findNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
    	$client->registerResult('withCoordinates');
    	 
    	$finder->findNear($this->collectionName, $reference,
                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
    	$client->registerResult('withReference');
    	
    	$finder->findNear($this->collectionName, $reference,
    			'myplaceholder.name == "Nonexistent Name"', array(), 2, "myplaceholder");
    	$client->registerResult('none');
    	
    	$result = $client->commit();
    	
    	//Assert the with coordinates results
    	$this->assertInternalType('array', $result['withCoordinates'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withCoordinates'], "Number of returned results should be 1");
    	
    	$withCoordinatesResult = reset($result['withCoordinates']);
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "The distance to the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$this->assertArrayNotHasKey($reference->getId(), $result['withReference'], "The reference was in the results list");
    	$this->assertInternalType('array', $result['withReference'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withReference'], "Number of returned results should be 1");
    	
    	$withReferenceResult = reset($result['withReference']);
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "The distance to the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "Returned result set should be an array");
    	
    	//Assert the none results
    	$this->assertInternalType('array', $result['none'], "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::findNear
     */
    public function testFindNearWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findNear($this->collectionName, $reference,
                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");


        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertArrayNotHasKey($reference->getId(), $results, "The reference was in the results list");
        $this->assertInternalType('array', $results, "Returned result set should be an array");
        $this->assertCount(1, $results, "Number of returned results should be 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findNear
     */
    public function testFindNearWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findNear($this->collectionName, $reference,
                "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents near a reference with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findNear
     */
    public function testFindNearWithEmptyResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findNear($this->collectionName, $reference,
                    "myplaceholder.name IN ['Priscah Jeptoo']", array(), 1, "myplaceholder");

        $this->assertInternalType('array', $results, "Returned result set is not an array");
        $this->assertEmpty($results, "Returned result set is not empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllNear
     */
    public function testFindAllNearWithCoordinates()
    {
        $results = $this->finder->findAllNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");

        $this->assertInternalType('array', $results, "Returned result set should be an array");
        $this->assertCount(1, $results, "Number of returned results should be 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findAllNear
     */
    public function testFindAllNearInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    	 
    	$client->begin();
    	$finder->findAllNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
    			"FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
    	$client->registerResult('withCoordinates');
    
    	$finder->findAllNear($this->collectionName, $reference,
    			"FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
    	$client->registerResult('withReference');
    	 
    	$finder->findAllNear($this->collectionName, $reference,
    			'FILTER myplaceholder.name == "Nonexistent Name"', array(), 2, "myplaceholder");
    	$client->registerResult('none');
    	 
    	$result = $client->commit();
    	 
    	//Assert the with coordinates results
    	$this->assertInternalType('array', $result['withCoordinates'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withCoordinates'], "Number of returned results should be 1");
    	 
    	$withCoordinatesResult = reset($result['withCoordinates']);
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "The distance from the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$this->assertArrayNotHasKey($reference->getId(), $result['withReference'], "The reference was in the results list");
    	$this->assertInternalType('array', $result['withReference'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withReference'], "Number of returned results should be 1");
    	 
    	$withReferenceResult = reset($result['withReference']);
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "The distance from the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "The coordinates should be an array");
    	 
    	//Assert the none results
    	$this->assertInternalType('array', $result['none'], "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllNear
     */
    public function testFindAllNearWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findAllNear($this->collectionName, $reference,
                "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");


        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertArrayNotHasKey($reference->getId(), $results, "The reference was in the results list");
        $this->assertInternalType('array', $results, "Returned result set should be an array");
        $this->assertCount(1, $results, "Number of returned results should be 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllNear
     */
    public function testFindAllNearWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findAllNear($this->collectionName, $reference,
                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), 2, "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents near a reference with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllNear
     */
    public function testFindAllNearWithEmptyResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findAllNear($this->collectionName, $reference,
                    "FILTER myplaceholder.name IN ['Priscah Jeptoo']", array(), 1, "myplaceholder");

        $this->assertInternalType('array', $results, "Returned result set is not an array");
        $this->assertEmpty($results, "Returned result set is not empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneNear
     */
    public function testFindOneNearWithCoordinates()
    {
        $result = $this->finder->findOneNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");

        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Horacio Manuel Cartes Jara');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findOneNear
     */
    public function testFindOneNearInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    
    	$client->begin();
    	$finder->findOneNear($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                 "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");
    	$client->registerResult('withCoordinates');
    
    	$finder->findOneNear($this->collectionName, $reference,
                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
    	$client->registerResult('withReference');
    
    	$finder->findOneNear($this->collectionName, $reference,
    			'myplaceholder.name == "Nonexistent Name"', array(), "myplaceholder");
    	$client->registerResult('none');
    
    	$result = $client->commit();
   
    	//Assert the with coordinates results
    	$withCoordinatesResult = $result['withCoordinates'];
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Horacio Manuel Cartes Jara', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "Distance from the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$withReferenceResult = $result['withReference'];
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "Distance from the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the none results
    	$this->assertNull($result['none'], "The result should be null");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneNear
     */
    public function testFindOneNearWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findOneNear($this->collectionName, $reference,
                "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");


        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertNotEquals($reference->getId(), $result->getId(), "The found pod should not be the reference pod");
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneNear
     */
    public function testFindOneNearWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findOneNear($this->collectionName, $reference,
                "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents near a reference with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneNear
     */
    public function testFindOneNearWithNullResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findOneNear($this->collectionName, $reference,
                    "myplaceholder.name IN ['Priscah Jeptoo']", array(), "myplaceholder");

        $this->assertNull($result, "Returned result should be null if nothing is found");
    }

    /**
     * @covers Paradox\toolbox\Finder::findWithin
     */
    public function testFindWithinWithCoordinates()
    {
        $results = $this->finder->findWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");

        $this->assertInternalType('array', $results, 'Result set is not an array');
        $this->assertCount(1, $results, 'Result set should only contain one result');

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Horacio Manuel Cartes Jara');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findWithin
     */
    public function testFindWithinInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();

    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    	
    	$client->begin();
    	$finder->findWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");
    	$client->registerResult('withCoordinates');
    
    	$finder->findWithin($this->collectionName, $reference,
                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
    	$client->registerResult('withReference');
    	 
    	$finder->findWithin($this->collectionName, $reference,
    			100000, 'myplaceholder.name == "Nonexistent Name"', array(), "myplaceholder");
    	$client->registerResult('none');

    	$result = $client->commit();
    	
    	//Assert the with coordinates results
    	$this->assertInternalType('array', $result['withCoordinates'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withCoordinates'], "Number of returned results should be 1");
    	 
    	$withCoordinatesResult = reset($result['withCoordinates']);
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Horacio Manuel Cartes Jara', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "The distance from the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$this->assertArrayNotHasKey($reference->getId(), $result['withReference'], "The reference was in the results list");
    	$this->assertInternalType('array', $result['withReference'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withReference'], "Number of returned results should be 1");
    	 
    	$withReferenceResult = reset($result['withReference']);
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "The distance from the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "The coordinates should be an array");
    	 
    	//Assert the none results
    	$this->assertInternalType('array', $result['none'], "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::findWithin
     */
    public function testFindWithinWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findWithin($this->collectionName, $reference,
                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");

        $this->assertInternalType('array', $results, 'Result set is not an array');
        $this->assertCount(1, $results, 'Result set should only contain one result');

        $result = reset($results);

        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertNotEquals($reference->getId(), $result->getId(), "The found pod should not be the reference pod");
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findWithin
     */
    public function testFindWithinWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findWithin($this->collectionName, $reference,
                100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents within a radius with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findWithin
     */
    public function testFindWithinWithEmptyResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findWithin($this->collectionName, $reference,
                    100, "myplaceholder.name IN ['Priscah Jeptoo']", array(), "myplaceholder");

        $this->assertInternalType('array', $result, "Returned result should be null if nothing is found");
        $this->assertEmpty($result, "Returned result set should be empty if nothing is found");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllWithin
     */
    public function testFindAllWithinWithCoordinates()
    {
        $results = $this->finder->findAllWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");

        $this->assertInternalType('array', $results, 'Result set is not an array');
        $this->assertCount(1, $results, 'Result set should only contain one result');

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Horacio Manuel Cartes Jara');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllWithin
     */
    public function testFindAllWithinInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    	 
    	$client->begin();
    	$finder->findAllWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
    			100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");
    	$client->registerResult('withCoordinates');
    
    	$finder->findAllWithin($this->collectionName, $reference,
    			100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
    	$client->registerResult('withReference');
    
    	$finder->findAllWithin($this->collectionName, $reference,
    			100000, 'FILTER myplaceholder.name == "Nonexistent Name"', array(), "myplaceholder");
    	$client->registerResult('none');
    
    	$result = $client->commit();
    	 
    	//Assert the with coordinates results
    	$this->assertInternalType('array', $result['withCoordinates'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withCoordinates'], "Number of returned results should be 1");
    
    	$withCoordinatesResult = reset($result['withCoordinates']);
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Horacio Manuel Cartes Jara', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "The distance from the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$this->assertArrayNotHasKey($reference->getId(), $result['withReference'], "The reference was in the results list");
    	$this->assertInternalType('array', $result['withReference'], "Returned result set should be an array");
    	$this->assertCount(1, $result['withReference'], "Number of returned results should be 1");
    
    	$withReferenceResult = reset($result['withReference']);
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "The distance from the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the none results
    	$this->assertInternalType('array', $result['none'], "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findAllWithin
     */
    public function testFindAllWithinWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $results = $this->finder->findAllWithin($this->collectionName, $reference,
                100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");

        $this->assertInternalType('array', $results, 'Result set is not an array');
        $this->assertCount(1, $results, 'Result set should only contain one result');

        $result = reset($results);

        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertNotEquals($reference->getId(), $result->getId(), "The found pod should not be the reference pod");
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllWithin
     */
    public function testFindAllWithinWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findAllWithin($this->collectionName, $reference,
                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents within a radius with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findAllWithin
     */
    public function testFindAllWithinWithEmptyResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findAllWithin($this->collectionName, $reference,
                    100, "FILTER myplaceholder.name IN ['Priscah Jeptoo']", array(), "myplaceholder");

        $this->assertInternalType('array', $result, "Returned result should be null if nothing is found");
        $this->assertEmpty($result, "Returned result set should be empty if nothing is found");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneWithin
     */
    public function testFindOneWithinWithCoordinates()
    {
        $result = $this->finder->findOneWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
                                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");

        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Horacio Manuel Cartes Jara');
        $this->assertInternalType('float', $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneWithin
     */
    public function testFindOneWithinInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	//Get the reference pod
    	$reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));
    	 
    	$client->begin();
    	$finder->findOneWithin($this->collectionName, array('latitude' => 48, 'longitude' => 48),
    			100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Horacio Manuel Cartes Jara'), "myplaceholder");
    	$client->registerResult('withCoordinates');
    
    	$finder->findOneWithin($this->collectionName, $reference,
    			100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
    	$client->registerResult('withReference');
    
    	$finder->findOneWithin($this->collectionName, $reference,
    			100000, 'myplaceholder.name == "Nonexistent Name"', array(), "myplaceholder");
    	$client->registerResult('none');
    
    	$result = $client->commit();
    	 
    	//Assert the with coordinates results
    	$withCoordinatesResult = $result['withCoordinates'];
    	$this->assertInstanceOf('Paradox\AModel', $withCoordinatesResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Horacio Manuel Cartes Jara', $withCoordinatesResult->get('name'), "The name of the found document does not match");
    	$this->assertInternalType('float', $withCoordinatesResult->getDistance(), "The distance from the coordinates should be a float");
    	$this->assertInternalType('array', $withCoordinatesResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the with reference results
    	$withReferenceResult = $result['withReference'];
    	$this->assertInstanceOf('Paradox\AModel', $withReferenceResult, 'Result items should have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $withReferenceResult->get('name'), "The name of the found document does not match");
    	$this->assertEquals(0, $withReferenceResult->getDistance(), "The distance from the reference should be 0");
    	$this->assertInternalType('array', $withReferenceResult->getCoordinates(), "The coordinates should be an array");
    
    	//Assert the none results
    	$this->assertNull($result['none'], "The result should be null");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::findOneWithin
     */
    public function testFindOneWithinWithReference()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findOneWithin($this->collectionName, $reference,
                100000, "myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");

        //Reference should not be in the results, because using a pod as a reference should exclude it from the list
        $this->assertNotEquals($reference->getId(), $result->getId(), "The found pod should not be the reference pod");
        $this->assertInstanceOf('Paradox\AModel', $result, 'Result items should have the type Paradox\AModel');
        $this->assertEquals($result->get('name'), 'Tsegaye Kebede');
        $this->assertEquals(0, $result->getDistance());
        $this->assertInternalType('array', $result->getCoordinates());
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneWithin
     */
    public function testFindOneWithinWithInvalidAQL()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        try {
            $results = $this->finder->findOneWithin($this->collectionName, $reference,
                100000, "FILTER myplaceholder.name IN [@name, 'Priscah Jeptoo']", array('name' => 'Tsegaye Kebede'), "myplaceholder");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Querying for documents within a radius with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::findOneWithin
     */
    public function testFindOneWithinWithEmptyResult()
    {
        //Get the reference pod
        $reference = $this->finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $result = $this->finder->findOneWithin($this->collectionName, $reference,
                    100, "myplaceholder.name IN ['Priscah Jeptoo']", array(), "myplaceholder");

        $this->assertNull($result, 'Result should be null if nothing is found');
    }

    /**
     * @covers Paradox\toolbox\Finder::search
     */
    public function testSearch()
    {
        $results = $this->finder->search($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');

        $this->assertInternalType('array', $results, 'Returned result is not an array');
        $this->assertCount(1, $results, "The number of documents found is not 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $result->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::search
     */
    public function testSearchInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();

    	$client->begin();
    	
    	$finder->search($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
    	$client->registerResult('found');
    	
    	$finder->search($this->collectionName, 'bio', 'marathon', "placeholder.name in [\"Nonexistent Person\"]", array(), 'placeholder');
    	$client->registerResult('none');
    
    	$result = $client->commit();
    
    	//Assert the found results
    	$this->assertInternalType('array', $result['found'], 'Returned result is not an array');
        $this->assertCount(1, $result['found'], "The number of documents found is not 1");

        $foundResult = reset($result['found']);
        $this->assertInstanceOf('Paradox\AModel', $foundResult, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $foundResult->get('name'), "The document returned does not have the same name as the queried name");
        
    	//Assert the none results
    	$this->assertInternalType('array', $result, "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::search
     */
    public function testSearchWithInvalidAQL()
    {

        try {
            $results = $this->finder->search($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Searching for documents using fulltext search with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::search
     */
    public function testSearchWithEmptyResult()
    {
        $results = $this->finder->search($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'NonExistentPerson'), 'placeholder');

        $this->assertInternalType('array', $results, 'Returned result is not an array');
        $this->assertEmpty($results, "Returned result set should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::searchAll
     */
    public function testSearchAll()
    {
        $results = $this->finder->searchAll($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');

        $this->assertInternalType('array', $results, 'Returned result is not an array');
        $this->assertCount(1, $results, "The number of documents found is not 1");

        $result = reset($results);
        $this->assertInstanceOf('Paradox\AModel', $result, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $result->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::searchAll
     */
    public function testSearchAllInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();

    	$client->begin();
    	 
    	$finder->searchAll($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
    	$client->registerResult('found');
    	 
    	$finder->searchAll($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [\"Nonexistent Person\"]", array(), 'placeholder');
    	$client->registerResult('none');
    
    	$result = $client->commit();
    
    	//Assert the found results
    	$this->assertInternalType('array', $result['found'], 'Returned result is not an array');
    	$this->assertCount(1, $result['found'], "The number of documents found is not 1");
    
    	$foundResult = reset($result['found']);
    	$this->assertInstanceOf('Paradox\AModel', $foundResult, 'The found document does not have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $foundResult->get('name'), "The document returned does not have the same name as the queried name");
    
    	//Assert the none results
    	$this->assertInternalType('array', $result, "The result should be an array");
    	$this->assertEmpty($result['none'], "The result should be empty");
    }

    /**
     * @covers Paradox\toolbox\Finder::searchAll
     */
    public function testSearchAllWithInvalidAQL()
    {

        try {
            $results = $this->finder->searchAll($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Searching for documents using fulltext search with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::searchAll
     */
    public function testSearchAllWithEmptyResult()
    {
        $results = $this->finder->searchAll($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [@name]", array('name' => 'NonExistentPerson'), 'placeholder');

        $this->assertInternalType('array', $results, 'Returned result is not an array');
        $this->assertEmpty($results, "Returned result set should be empty");
    }

        /**
     * @covers Paradox\toolbox\Finder::searchForOne
     */
    public function testSearchForOne()
    {
        $result = $this->finder->searchForOne($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');

        $this->assertInstanceOf('Paradox\AModel', $result, 'The found document does not have the type Paradox\AModel');
        $this->assertEquals('Tsegaye Kebede', $result->get('name'), "The document returned does not have the same name as the queried name");
    }
    
    /**
     * @covers Paradox\toolbox\Finder::searchForOne
     */
    public function testSearchForOneInTransaction()
    {
    	$client = $this->getClient();
    	$finder = $client->getToolbox()->getFinder();
    
    	$client->begin();
    
    	$finder->searchForOne($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
    	$client->registerResult('found');
    
    	$finder->searchForOne($this->collectionName, 'bio', 'marathon', "placeholder.name in [\"Nonexistent Person\"]", array(), 'placeholder');
    	$client->registerResult('none');
    
    	$result = $client->commit();
    
    	//Assert the found results
    	$foundResult = $result['found'];
    	$this->assertInstanceOf('Paradox\AModel', $foundResult, 'The found document does not have the type Paradox\AModel');
    	$this->assertEquals('Tsegaye Kebede', $foundResult->get('name'), "The document returned does not have the same name as the queried name");
    
    	//Assert the none results
    	$this->assertNull($result['none'], "The result should be null");
    }

    /**
     * @covers Paradox\toolbox\Finder::searchForOne
     */
    public function testSearchForOneWithInvalidAQL()
    {

        try {
            $results = $this->finder->searchForOne($this->collectionName, 'bio', 'marathon', "FILTER placeholder.name in [@name]", array('name' => 'Tsegaye Kebede'), 'placeholder');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Searching for documents using fulltext search with invalid AQL did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Finder::searchForOne
     */
    public function testSearchForOneWithEmptyResult()
    {
        $result = $this->finder->searchForOne($this->collectionName, 'bio', 'marathon', "placeholder.name in [@name]", array('name' => 'NonExistentPerson'), 'placeholder');

        $this->assertNull($result, "Result should be null if nothing is found");
    }


    /**
     * @covers Paradox\toolbox\Finder::generateReferenceData
     */
    public function testGenerateReferenceDataWithModel()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('generateReferenceData');
        $method->setAccessible(true);

        $finder = new Finder($this->getClient()->getToolbox());

        $document = $finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Horacio Manuel Cartes Jara'));

        $reference = $method->invoke($finder, $document);
        $this->assertInternalType('array', $reference, "The generated reference should be an array");
        $this->assertEquals(48.1, $reference['latitude'], "Generated reference's latitude does not match the document's");
        $this->assertEquals(48.1, $reference['longitude'], "Generated reference's longitude does not match the document's");
        $this->assertEquals($document->getId(), $reference['podId'], "Generated reference's id does not match the document's");
    }

    /**
     * @covers Paradox\toolbox\Finder::generateReferenceData
     */
    public function testGenerateReferenceDataOnCollectionWithoutGeoIndex()
    {
        $client = $this->getClient();
        $client->createCollection($this->collectionName . 'NoIndices');
        $document = $client->dispense($this->collectionName . 'NoIndices');
        $client->store($document);

        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('generateReferenceData');
        $method->setAccessible(true);

        $finder = new Finder($client->getToolbox());

        $document = $finder->any($this->collectionName . 'NoIndices');

        try {
            $reference = $method->invoke($finder, $document);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');
            $client->deleteCollection($this->collectionName . 'NoIndices');

            return;
        }

        $client->deleteCollection($this->collectionName . 'NoIndices');
        $this->fail("Generating geo data for document in collection without geo index did not throw an exception");

    }

    /**
     * @covers Paradox\toolbox\Finder::generateReferenceData
     */
    public function testGenerateReferenceDataUsingArray()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('generateReferenceData');
        $method->setAccessible(true);

        $finder = new Finder($this->getClient()->getToolbox());

        $coordinates = array('latitude' => 48, 'longitude' => 48);

        $reference = $method->invoke($finder, $coordinates);

        $this->assertInternalType('array', $reference, "The generated reference should be an array");
        $this->assertEquals(48, $reference['latitude'], "Generated reference's latitude does not match the document's");
        $this->assertEquals(48, $reference['longitude'], "Generated reference's longitude does not match the document's");
        $this->assertNull($reference['podId'], "Generated reference's should not have a pod id");

    }

    /**
     * @covers Paradox\toolbox\Finder::generateReferenceData
     */
    public function testGenerateReferenceDataUsingInvalidArray()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('generateReferenceData');
        $method->setAccessible(true);

        $finder = new Finder($this->getClient()->getToolbox());

        $coordinates = array('somekey' => 'somevalue');

        try {
            $reference = $method->invoke($finder, $coordinates);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Generating geo data using an invalid array did not throw an exception");

    }

    /**
     * @covers Paradox\toolbox\Finder::generateReferenceData
     */
    public function testGenerateReferenceDataUsingInvalidInput()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('generateReferenceData');
        $method->setAccessible(true);

        $finder = new Finder($this->getClient()->getToolbox());

        try {
            $reference = $method->invoke($finder, 'InvalidInput');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail("Generating geo data using an invalid input did not throw an exception");

    }

    /**
     * @covers Paradox\toolbox\Finder::convertToPods
     */
    public function testConvertToPods()
    {
        $client = $this->getClient();

        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('convertToPods');
        $method->setAccessible(true);

        $finder = new Finder($client->getToolbox());

        $queryResult = $client->getAll("FOR u in $this->collectionName FILTER u.name == @name return u", array('name' => 'Tsegaye Kebede'));
        $queryResult[0]['_paradox_distance_parameter'] = 100000;

        $referencePod = $finder->findOne($this->collectionName, 'doc.name == @name', array('name' => 'Priscah Jeptoo'));

        $geoInfo = $referencePod->getPod()->getCoordinates();
        $geoInfo['podId'] = $referencePod->getId();

        $pods = $method->invoke($finder, $this->collectionName, $queryResult, $geoInfo);

        $this->assertInternalType('array', $pods, "Converted pods are not in an array");
        $this->assertCount(1, $pods, "Only 1 result is expected");

        $podResult = reset($pods);
        $this->assertInstanceOf('Paradox\AModel', $podResult, 'Result is not of type Paradox\AModel');

        $singleResult = reset($queryResult);

        $this->assertEquals($singleResult['_id'], $podResult->getId(), "The id of the converted pod and the query result does not match");
        $this->assertEquals($singleResult['_key'], $podResult->getKey(), "The key of the converted pod and the query result does not match");
        $this->assertEquals($singleResult['_rev'], $podResult->getRevision(), "The revision of the converted pod and the query result does not match");
        $this->assertEquals($referencePod->getPod()->getCoordinates(), $podResult->getReferenceCoordinates(), "The coordinates to the reference pod does not match");
        $this->assertEquals(100000,	$podResult->getDistance(), "The distance does not match");
        $this->assertEquals($referencePod->getId(), $podResult->getReferencePod()->getId(), "The referenced pod does not match");

        foreach ($singleResult as $key => $value) {

            if (substr($key, 0, 1) != "_") {
                $this->assertEquals($value, $podResult->get($key), "The value of the key in the converted pod does not match the query's value");
            }
        }
    }

    /**
     * @covers Paradox\toolbox\Finder::getCollectionName
     */
    public function testGetCollectionName()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('getCollectionName');
        $method->setAccessible(true);

        $finder = new Finder($this->getClient()->getToolbox());

        $result = $method->invoke($finder, $this->collectionName);

        $this->assertEquals($this->collectionName, $result, "The generated collection name does not match the input collection name");
    }

    /**
     * @covers Paradox\toolbox\Finder::getCollectionName
     */
    public function testGetCollectionNameForGraph()
    {
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Finder');

        $method = $reflectionClass->getMethod('getCollectionName');
        $method->setAccessible(true);

        $toolbox = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $this->graphName)->getToolbox();
        $finder = new Finder($toolbox);

        $result = $method->invoke($finder, "vertex");
        $this->assertEquals($toolbox->getVertexCollectionName(), $result, "The generated vertex collection name does not match the expected vertex collection name");

        $result = $method->invoke($finder, "edge");
        $this->assertEquals($toolbox->getEdgeCollectionName(), $result, "The generated edge collection name does not match the expected edge collection name");

        try {
            $result = $method->invoke($finder, "SomeRandomCollection");
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\FinderException', $e, 'Exception thrown was not a Paradox\exceptions\FinderException');

            return;
        }

        $this->fail('Getting the collection name for something other than "vertex" or "vertex" did not throw a collection');
    }
}
