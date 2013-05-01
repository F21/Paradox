<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\pod\Document;

/**
 * Tests for the abstract model class.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class AModelTest extends Base
{
    /**
     * An instance of a mocked model.
     * @var AModel
     */
    protected $model;

    /**
     * An instance of a pod.
     * @var Document
     */
    protected $pod;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->pod = $this->getClient()->dispense('testCollection')->getPod();
        $this->model = $this->getMockForAbstractClass('Paradox\AModel');
    }

    /**
     * @covers Paradox\AModel::loadPod
     */
    public function testLoadPod()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AModel');

        //Then we need to get the property we wish to test
        //and make it accessible
        $pod = $reflectionClass->getProperty('_pod');
        $pod->setAccessible(true);

        $this->assertNull($pod->getValue($this->model), "The model should not have a pod");
        $this->model->loadPod($this->pod);

        $this->assertInstanceOf('Paradox\pod\Document', $pod->getValue($this->model), "The model should have a pod loaded");
    }

    /**
     * @covers Paradox\AModel::loadPod
     */
    public function testLoadPodInModelThatHasAPod()
    {
        //First we need to create a ReflectionClass object
        //passing in the class name as a variable
        $reflectionClass = new \ReflectionClass('Paradox\AModel');

        //Then we need to get the property we wish to test
        //and make it accessible
        $pod = $reflectionClass->getProperty('_pod');
        $pod->setAccessible(true);

        $this->assertNull($pod->getValue($this->model), "The model should not have a pod");
        $this->model->loadPod($this->pod);

        $this->assertInstanceOf('Paradox\pod\Document', $pod->getValue($this->model), "The model should have a pod loaded");

        //Try loading a pod again
        $pod = $this->getClient()->dispense('testCollection')->getPod();

        try {
            $this->model->loadPod($pod);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Paradox\exceptions\ModelException', $e, 'Exception thrown was not a Paradox\exceptions\ModelException');

            return;
        }

        $this->fail('Loading a pod into a model that already has a pod did not throw an exception');
    }

    /**
     * @covers Paradox\AModel::getPod
     */
    public function testGetPod()
    {
        $this->model->loadPod($this->pod);
        $this->assertInstanceOf('Paradox\pod\Document', $this->model->getPod(), 'The returned pod should be an instance of Paradox\pod\Document or it\'s children');

    }

    /**
     * @covers Paradox\AModel::afterDispense
     */
    public function testAfterDispense()
    {
        $this->assertNull($this->model->afterDispense(), "The afterDispense() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::afterOpen
     */
    public function testAfterOpen()
    {
        $this->assertNull($this->model->afterOpen(), "The afterOpen() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::beforeStore
     */
    public function testBeforeStore()
    {
        $this->assertNull($this->model->beforeStore(), "The beforeStore() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::afterStore
     */
    public function testAfterStore()
    {
        $this->assertNull($this->model->afterStore(), "The afterStore() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::beforeDelete
     */
    public function testBeforeDelete()
    {
        $this->assertNull($this->model->beforeDelete(), "The beforeDelete() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::afterDelete
     */
    public function testAfterDelete()
    {
        $this->assertNull($this->model->afterDelete(), "The afterDelete() method should return nothing in the abstract implementation");
    }

    /**
     * @covers Paradox\AModel::__call
     */
    public function test__call()
    {
        $this->model->loadPod($this->pod);

        $this->assertNull($this->model->getId(), 'The new pod should have no id');
    }
}
