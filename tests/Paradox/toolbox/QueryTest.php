<?php
namespace tests\Paradox\toolbox;
use tests\Base;
use Paradox\toolbox\Query;

/**
 * Tests for the query helper.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class QueryTest extends Base
{
    /**
     * The collection name for this test case.
     * @var string
     */
    protected $collectionName = 'QueryTestCollection';

    /**
     * The graph name for this test case.
     * @var string
     */
    protected $graphName = 'QueryTestGraph';

    /**
     * Stores an instance of the query helper.
     * @var Query
     */
    protected $query;

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
        $this->query = $this->getQuery();
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
            //Ignore exception
        }

        try {
            $client->deleteGraph($this->graphName);
        } catch (\Exception $e) {
            //Ignore exception
        }
    }

    /**
     * Convinence function to get the query helper.
     * @param  string                 $graph The name of the graph if we want the query helper to manage a graph.
     * @return \Paradox\toolbox\Query
     */
    protected function getQuery($graph = null)
    {
        $client = $this->getClient($this->getDefaultEndpoint(), $this->getDefaultUsername(), $this->getDefaultPassword(), $graph);

        return $client->getToolbox()->getQuery();
    }

    /**
     * @covers Paradox\toolbox\Query::__construct
     */
    public function testConstructor()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\toolbox\Query');

        //Then we need to get the property we wish to test
        //and make it accessible
        $property = $reflectionClass->getProperty('_toolbox');
        $property->setAccessible(true);

        //We need to create an empty object to pass to
        //ReflectionProperty's getValue method
        $manager = new Query($this->getClient()->getToolbox());

        $this->assertInstanceOf('Paradox\Toolbox', $property->getValue($manager), 'Query constructor did not store a Paradox\Toolbox.');
    }

    /**
     * @covers Paradox\toolbox\Query::getAll
     */
    public function testGetAll()
    {
        $query = "FOR doc in @@collection return doc";

        $results = $this->query->getAll($query, array('@collection' => $this->collectionName));

        $this->assertInternalType('array', $results, "The result set should be an array");
        $this->assertCount(4, $results, "The result set should contain 4 results");

        foreach ($results as $result) {
            $this->assertInternalType('array', $result, "Each result in the result set should be an array");
        }
    }

    /**
     * @covers Paradox\toolbox\Query::getAll
     */
    public function testGetAllWithInvalidAQL()
    {
        $query = "FOR doc in @@collection return u";

        try {
            $results = $this->query->getAll($query);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\QueryException', $e, 'Exception thrown was not a Paradox\exceptions\QueryException');

            return;
        }

        $this->fail("Querying with an invalid AQL or missing parameter array did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Query::getOne
     */
    public function testGetOne()
    {
        $query = "FOR doc in @@collection return doc";

        $result = $this->query->getOne($query, array('@collection' => $this->collectionName));

        $this->assertInternalType('array', $result, "The result should be an array");
    }

    /**
     * @covers Paradox\toolbox\Query::getOne
     */
    public function testGetOneWithNoResults()
    {
        $query = "FOR doc in @@collection FILTER doc.name == @nonexistentname return doc";

        $result = $this->query->getOne($query, array('@collection' => $this->collectionName, 'nonexistentname' => "PersonThatDoesNotExist"));

        $this->assertNull($result, "The result should be null since nothing was found");
    }

    /**
     * @covers Paradox\toolbox\Query::getOne
     */
    public function testGetOneWithInvalidAQL()
    {
        $query = "FOR doc in @@collection return u";

        try {
            $results = $this->query->getOne($query);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\QueryException', $e, 'Exception thrown was not a Paradox\exceptions\QueryException');

            return;
        }

        $this->fail("Querying with an invalid AQL or missing parameter array did not throw an exception");
    }

    /**
     * @covers Paradox\toolbox\Query::explain
     */
    public function testExplain()
    {
        $query = "FOR doc in @@collection return doc";

        $explain = $this->query->explain($query, array('@collection' => $this->collectionName));

        $this->assertInternalType('array', $explain, "The result set should be an array");
    }

    /**
     * @covers Paradox\toolbox\Query::explain
     */
    public function testExplainWithInvalidAQL()
    {
        $query = "FOR doc in @@collection return u";

        try {
            $results = $this->query->explain($query);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\QueryException', $e, 'Exception thrown was not a Paradox\exceptions\QueryException');

            return;
        }

        $this->fail("Running explain with an invalid AQL or missing parameter array did not throw an exception");
    }
}
