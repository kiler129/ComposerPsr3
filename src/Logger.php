<?php
/*
 * This file is part of noFlash ComposerPsr3 package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace noFlash\ComposerPsr3;

use Composer\IO\IOInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Main Logger class
 *
 * @package noFlash\ComposerPsr3
 * @author  Grzegorz Zdanowski <grzegorz@noflash.pl>
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var string Log emergency level
     */
    const LEVEL_EMERGENCY = 'EMERG';

    /**
     * @var string Log alert level
     */
    const LEVEL_ALERT = 'ALERT';

    /**
     * @var string Log critical level
     */
    const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * @var string Log error level
     */
    const LEVEL_ERROR = 'ERROR';

    /**
     * @var string Log warning level
     */
    const LEVEL_WARNING = 'WARN';

    /**
     * @var string Log notice level
     */
    const LEVEL_NOTICE = 'NOTICE';

    /**
     * @var string Log info level
     */
    const LEVEL_INFO = 'INFO';

    /**
     * @var string Log debug level
     */
    const LEVEL_DEBUG = 'DEBUG';

    /**
     * @var string[] Level tags
     */
    const LEVEL_TAGS = [
        self::LEVEL_ERROR   => 'error',
        self::LEVEL_WARNING => 'warning',
    ];

    /**
     * @var array Verbosity level mapping
     */
    private $levelsVerbosity = [
        self::LEVEL_EMERGENCY => IOInterface::NORMAL,
        self::LEVEL_ALERT     => IOInterface::NORMAL,
        self::LEVEL_CRITICAL  => IOInterface::NORMAL,
        self::LEVEL_ERROR     => IOInterface::NORMAL,
        self::LEVEL_WARNING   => IOInterface::VERBOSE,
        self::LEVEL_NOTICE    => IOInterface::VERBOSE,
        self::LEVEL_INFO      => IOInterface::VERY_VERBOSE,
        self::LEVEL_DEBUG     => IOInterface::DEBUG
    ];

    /**
     * @var IOInterface Input/Output helper interface
     */
    private $IO;

    /**
     * @var string printf-complaint line format
     */
    private $lineFormat = '<%1$s> [%2$s] %3$s';

    /**
     * @var string date()-complaint format used in log lines
     */
    private $datetimeFormat = 'd.m.Y H:i:s';

    /**
     * Logger constructor.
     *
     * @param IOInterface $IO Input/Output helper interface
     */
    public function __construct(IOInterface $IO)
    {
        $this->IO = $IO;
    }

    /**
     * Handles any custom log level you can imagine, even if it's paranoia level, just
     * call:
     * $instance->paranoia('Aaaa!!!')
     *
     * @param       $level
     * @param array $arguments
     *
     * @throws \InvalidArgumentException
     */
    public function __call($level, $arguments)
    {
        $message = (isset($arguments[0])) ? $arguments[0] : "";
        $context = (isset($arguments[1])) ? $arguments[1] : [];

        $this->log($level, $message, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @todo Docbug - $context["exception"] aren't detected as Exception instance
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $level = strtoupper($level);

        $exceptionText = (isset($context['exception']) &&
                          is_object($context['exception']) &&
                          $context['exception'] instanceof \Exception) ?
                            print_r($context['exception'], true) : '';

        $contextText = '';
        if (!empty($context) && is_array($context)) {
            $contextText = print_r($context, true);
        }

        $message = sprintf(
            $this->lineFormat, //Line format
            date($this->datetimeFormat), //%1$s - date
            $level, //%2$s - log level
            $message, //%3$s - text
            $contextText, //%4$s - context
            $exceptionText, //%5$s - exception (if available)
            time() //%1$d - unix timestamp
        );

        if (array_key_exists($level, self::LEVEL_TAGS)) {
            $message = sprintf('<%1$s>%2$s</%1$s>', self::LEVEL_TAGS[$level], $message);
        }

        $verbosity = isset($this->levelsVerbosity[$level]) ? $this->levelsVerbosity[$level] : IOInterface::NORMAL;

        $this->IO->write($message, true, $verbosity);
    }

    /**
     * Specifies how log line should look.
     * There are 6 modifiers:
     *  %1$s - date
     *  %2$s - log level (uppercased)
     *  %3$s - message text
     *  %4$s - context (formatted by print_r())
     *  %5$s - exception if available (formatted by print_r())
     *  %1$d - unix timestamp
     *
     * @see print_r()
     *
     * @param string $format Log line format
     *
     * @return void
     */
    public function setLineFormat($format)
    {
        $this->lineFormat = $format;
    }

    /**
     * Accepts any date() compliant format.
     *
     * @see date()
     *
     * @param string $format Datetime format
     *
     * @return void
     */
    public function setDatetimeFormat($format)
    {
        $this->datetimeFormat = $format;
    }

    /**
     * Provides currently set date() format.
     *
     * @see date()
     *
     * @return string Datetime format
     */
    public function getDateTimeFormat()
    {
        return $this->datetimeFormat;
    }

    /**
     * Get Level Verbosity Map
     *
     * @return array
     */
    public function getLevelVerbosityMap()
    {
        return $this->levelsVerbosity;
    }

    /**
     * Defines log level priority
     *
     * Note: method doesn't prevent you from changing built-in log level, however it's not
     * recommended
     *
     * @param string  $level
     * @param integer $verbosity
     *
     * @return void
     */
    public function setLevelVerbosity($level, $verbosity)
    {
        if (!is_integer($verbosity)) {
            throw new InvalidArgumentException('Verbosity must be an integer');
        }

        $level = strtoupper($level);
        $this->levelsVerbosity[$level] = $verbosity;
    }
}
