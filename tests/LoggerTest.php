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
        $shoutReflection = new \ReflectionClass(Logger::class);
        $this->assertTrue($shoutReflection->isSubclassOf(LoggerInterface::class));
    }
    public function testExtendsAbstractLogger()
    {
        $shoutReflection = new \ReflectionClass(Logger::class);
        $this->assertTrue($shoutReflection->isSubclassOf(AbstractLogger::class));
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
