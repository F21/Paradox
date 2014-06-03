<?php
namespace tests\Paradox;
use tests\Base;
use Paradox\DefaultModelFormatter;
use Paradox\pod\Document;

/**
 * Tests for the default formatter.
 *
 * @author Francis Chuang <francis.chuang@gmail.com>
 * @link https://github.com/F21/Paradox
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2 License
 */
class DefaultModelFormatterTest extends Base
{
    /**
     * Stores a reference to the model formatter.
     * @var DefaultModelFormatter
     */
    protected $modelFormatter;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->modelFormatter = new DefaultModelFormatter();
    }

    /**
     * @covers Paradox\DefaultModelFormatter::formatModel
     */
    public function testFormatModel()
    {
        $client = $this->getClient();

        $pod = new Document($client->getToolbox(), 'test');

        $result = $this->modelFormatter->formatModel($pod, true);

        $this->assertEquals('\Paradox\GenericModel', $result, 'The default model formatter should always return "\Paradox\GenericModel"');
    }
}
