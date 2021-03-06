<?php


namespace noFlash\ComposerPsr3\Tests;

use Composer\IO\IOInterface;
use noFlash\ComposerPsr3\Logger;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IOInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $IO;

    /**
     * @var Logger
     */
    private $subjectUnderTest;

    public function setUp()
    {
        $this->IO = $this->getMockForAbstractClass(IOInterface::class);
        $this->subjectUnderTest = new Logger($this->IO);
    }

    public function testImplementsLoggerInterface()
    {
        $reflection = new \ReflectionClass(Logger::class);
        $this->assertTrue($reflection->isSubclassOf(LoggerInterface::class));
    }
    public function testExtendsAbstractLogger()
    {
        $reflection = new \ReflectionClass(Logger::class);
        $this->assertTrue($reflection->isSubclassOf(AbstractLogger::class));
    }

    public function standardLogLevelsProvider()
    {
        return [
            ['LEVEL_EMERGENCY', 'EMERG'],
            ['LEVEL_ALERT', 'ALERT'],
            ['LEVEL_CRITICAL', 'CRITICAL'],
            ['LEVEL_ERROR', 'ERROR'],
            ['LEVEL_WARNING', 'WARN'],
            ['LEVEL_NOTICE', 'NOTICE'],
            ['LEVEL_INFO', 'INFO'],
            ['LEVEL_DEBUG', 'DEBUG']
        ];
    }

    /**
     * @dataProvider standardLogLevelsProvider
     */
    public function testClassProvidesAllStandardLogLevels($constantName, $constantValue)
    {
        $constantName = Logger::class . '::'. $constantName;
        $this->assertTrue(defined($constantName), "Constant $constantName not found");
        $this->assertEquals(constant($constantName), $constantValue);
    }

    public function testLogGetsWritten()
    {
        $this->IO->expects($this->once())->method('write');
        $this->subjectUnderTest->log('', '');
    }

    public function testDefaultDateFormatIsProvided()
    {
        $this->assertNotFalse(@date($this->subjectUnderTest->getDateTimeFormat()));
    }

    public function testDateFormatCanBeSet()
    {
        $this->subjectUnderTest->setDatetimeFormat('YYYYMMMhhhii');
        $this->assertSame('YYYYMMMhhhii', $this->subjectUnderTest->getDateTimeFormat());
    }

    public function testLogLineProvidesCorrectDate()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($this->equalTo(date('d.m.Y')));

        $this->subjectUnderTest->setDatetimeFormat('d.m.Y');
        $this->subjectUnderTest->setLineFormat('%1$s');
        $this->subjectUnderTest->log('', '');
    }

    public function testDefaultLogLineProvidesCorrectDate()
    {
        $expectedDate = date($this->subjectUnderTest->getDateTimeFormat());

        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains($expectedDate));

        $this->subjectUnderTest->log('', '');
    }


    public function testLogLineProvidesLogLevel()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with(Logger::LEVEL_INFO);

        $this->subjectUnderTest->setLineFormat('%2$s');
        //Standard log level using log() method
        $this->subjectUnderTest->log(Logger::LEVEL_INFO, '');
    }

    public function testDefaultLogLineProvidesLogLevel()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains(Logger::LEVEL_DEBUG));

        $this->subjectUnderTest->log(Logger::LEVEL_DEBUG, '');
    }

    public function testLogLineConvertsStandardLevelToUppercase()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with(Logger::LEVEL_INFO);

        $this->subjectUnderTest->setLineFormat('%2$s');
        $this->subjectUnderTest->log('iNfO', '');
    }

    public function testLogLineConvertsStandardLevelToUppercaseWhileUsingMagicMethod()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with(Logger::LEVEL_INFO);

        $this->subjectUnderTest->setLineFormat('%2$s');
        $this->subjectUnderTest->info('');
    }

    public function testLogLineConvertsCustomLevelToUppercase()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with('CUSTOM');

        $this->subjectUnderTest->setLineFormat('%2$s');
        $this->subjectUnderTest->log('cUsToM', '');
    }

    public function testLogLineConvertsCustomLevelToUppercaseWhileUsingMagicMethod()
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with('CUSTOM');

        $this->subjectUnderTest->setLineFormat('%2$s');
        $this->subjectUnderTest->cUsToM('');
    }

    public function logMessagesProvider()
    {
        return array(
            array('Simple test'),
            array("New\nline"),
            array('UTF: ☃')
        );
    }

    /**
     * @dataProvider logMessagesProvider
     */
    public function testLogLineProvidesLogMessage($message)
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($message);

        $this->subjectUnderTest->setLineFormat('%3$s');
        $this->subjectUnderTest->log('', $message);
    }

    /**
     * @dataProvider logMessagesProvider
     */
    public function testDefaultLogLineProvidesLogMessage($message)
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains($message));

        $this->subjectUnderTest->log('', $message);
    }

    public function contextAwareLogMessagesProvider()
    {
        return [
            ['Simple {foo} test', ['foo' => 'bar'], 'Simple bar test'],
            [
                'Two {place} holders with {text}',
                ['place' => 'beer', 'text' => 'juice'],
                'Two beer holders with juice'
            ],
            ['Two {bars} in {bars}', ['bars' => 'foo'], 'Two foo in foo'],
            [
                'Test {with} unknown {placeholder}',
                ['placeholder' => 'beer'],
                'Test {with} unknown beer'
            ]
        ];
    }

    /**
     * @dataProvider contextAwareLogMessagesProvider
     */
    public function testLogLineParsesContext($rawLog, $context, $parsedMessage)
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($parsedMessage);

        $this->subjectUnderTest->setLineFormat('%3$s');
        $this->subjectUnderTest->log('', $rawLog, $context);
    }

    public function testLogLineProvidesContextRepresentation()
    {
        $testContext = array(
            'test1' => array('test2', 'test3'),
            null
        );

        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with(print_r($testContext, true));

        $this->subjectUnderTest->setLineFormat('%4$s');
        $this->subjectUnderTest->log('', '', $testContext);
    }

    public function exceptionsDataProvider()
    {
        return [
            [['exception' => 'I am not an exception'], false],
            [['exception' => new \stdClass()], false],
            [['notException' => new \Exception()], false],
            [['exception' => new \Exception()], true]
        ];
    }

    /**
     * @dataProvider exceptionsDataProvider
     */
    public function testLogLineProvidesProperExceptionRepresentation($context, $valid)
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with($valid ? $this->stringStartsWith('Exception Object') : '');

        $this->subjectUnderTest->setLineFormat('%5$s');
        $this->subjectUnderTest->log('', '', $context);
    }

    /**
     * @dataProvider standardLogLevelsProvider
     */
    public function testVerbosityIsSetForAllStandardLevels($constantName, $constantValue)
    {
        $constantName = Logger::class . '::'. $constantName;
        $constantValue = constant($constantName);

        $map = $this->subjectUnderTest->getLevelVerbosityMap();
        $this->assertArrayHasKey($constantValue, $map);
        $this->assertInternalType('integer', $map[$constantValue]);
    }

    public function verbosityLevelProvider()
    {
        $IO = $this->getMockForAbstractClass(IOInterface::class);

        foreach ((new Logger($IO))->getLevelVerbosityMap() as $level => $verb) {
            yield [$level, $verb];
        }
    }

    /**
     * @dataProvider verbosityLevelProvider
     */
    public function testIOInterfaceGetsProperVerbosityLevel($level, $verbosity)
    {
        $this->IO
            ->expects($this->once())
            ->method('write')
            ->with(
                $this->anything(),
                $this->anything(),
                $verbosity
            );

        $this->subjectUnderTest->log($level, '');
    }

    public function testCustomVerbosityLevelCanBeSet()
    {
        $customLevel = 'CUSTOM';
        $customVerbosity = 1234;

        $this->subjectUnderTest->setLevelVerbosity($customLevel, $customVerbosity);

        $map = $this->subjectUnderTest->getLevelVerbosityMap();
        $this->assertArrayHasKey($customLevel, $map);
        $this->assertSame($customVerbosity, $map[$customLevel]);
    }

    public function testStandardVerbosityLevelCanBeSet()
    {
        $customLevel = Logger::LEVEL_DEBUG;
        $customVerbosity = 69;

        $this->subjectUnderTest->setLevelVerbosity($customLevel, $customVerbosity);

        $map = $this->subjectUnderTest->getLevelVerbosityMap();
        $this->assertArrayHasKey($customLevel, $map);
        $this->assertSame($customVerbosity, $map[$customLevel]);
    }

    public function testNonIntegerVerbosityLevelThrowsException()
    {
        $this->setExpectedException(
            \Psr\Log\InvalidArgumentException::class,
            'Verbosity must be an integer'
        );

        $this->subjectUnderTest->setLevelVerbosity(Logger::LEVEL_DEBUG, M_PI);
    }

    public function testNonNumericVerbosityLevelThrowsException()
    {
        $this->setExpectedException(
            \Psr\Log\InvalidArgumentException::class,
            'Verbosity must be an integer'
        );

        $this->subjectUnderTest->setLevelVerbosity(Logger::LEVEL_INFO, 'foo');
    }
}
