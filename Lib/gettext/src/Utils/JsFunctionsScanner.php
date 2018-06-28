<?php

namespace Gettext\Utils;

class JsFunctionsScanner extends FunctionsScanner
{
    protected $code;
    protected $status = [];

    /**
     * Constructor.
     *
     * @param string $code The php code to scan
     */
    public function __construct($code)
    {
        // Normalize newline characters
        $this->code = str_replace(["\r\n", "\n\r", "\r"], "\n", $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(array $constants = [])
    {
        $length = strlen($this->code);
        $line = 1;
        $buffer = '';
        $functions = [];
        $bufferFunctions = [];
        $char = null;

        for ($pos = 0; $pos < $length; ++$pos) {
            $prev = $char;
            $char = $this->code[$pos];
            $next = isset($this->code[$pos + 1]) ? $this->code[$pos + 1] : null;

            switch ($char) {
                case '\\':
                    $prev = $char;
                    $char = $next;
                    $pos++;
                    $next = isset($this->code[$pos]) ? $this->code[$pos] : null;
                    break;

                case "\n":
                    ++$line;

                    if ($this->status('line-comment')) {
                        $this->upStatus();
                    }
                    break;

                case '/':
                    switch ($this->status()) {
                        case 'simple-quote':
                        case 'double-quote':
                        case 'line-comment':
                            break;

                        case 'block-comment':
                            if ($prev === '*') {
                                $this->upStatus();
                            }
                            break;

                        default:
                            if ($next === '/') {
                                $this->downStatus('line-comment');
                            } elseif ($next === '*') {
                                $this->downStatus('block-comment');
                            }
                            break;
                    }
                    break;

                case "'":
                    switch ($this->status()) {
                        case 'simple-quote':
                            $this->upStatus();
                            break;

                        case 'line-comment':
                        case 'block-comment':
                        case 'double-quote':
                            break;

                        default:
                            $this->downStatus('simple-quote');
                            break;
                    }
                    break;

                case '"':
                    switch ($this->status()) {
                        case 'double-quote':
                            $this->upStatus();
                            break;

                        case 'line-comment':
                        case 'block-comment':
                        case 'simple-quote':
                            break;

                        default:
                            $this->downStatus('double-quote');
                            break;
                    }
                    break;

                case '(':
                    switch ($this->status()) {
                        case 'simple-quote':
                        case 'double-quote':
                        case 'line-comment':
                        case 'block-comment':
                        case 'line-comment':
                            break;

                        default:
                            if ($buffer && preg_match('/(\w+)$/', $buffer, $matches)) {
                                $this->downStatus('function');
                                array_unshift($bufferFunctions, [$matches[1], $line, []]);
                                $buffer = '';
                                continue 3;
                            }
                            break;
                    }
                    break;

                case ')':
                    switch ($this->status()) {
                        case 'function':
                            if (($argument = self::prepareArgument($buffer))) {
                                $bufferFunctions[0][2][] = $argument;
                            }

                            if (!empty($bufferFunctions)) {
                                $functions[] = array_shift($bufferFunctions);
                            }

                            $this->upStatus();
                            $buffer = '';
                            continue 3;
                    }
                    break;

                case ',':
                    switch ($this->status()) {
                        case 'function':
                            if (($argument = self::prepareArgument($buffer))) {
                                $bufferFunctions[0][2][] = $argument;
                            }

                            $buffer = '';
                            continue 3;
                    }
                    break;

                case ' ':
                case '\t':
                    switch ($this->status()) {
                        case 'double-quote':
                        case 'simple-quote':
                            break;

                        default:
                            continue 3;
                    }
                    break;
            }

            switch ($this->status()) {
                case 'line-comment':
                case 'block-comment':
                    break;

                default:
                    $buffer .= $char;
                    break;
            }
        }

        return $functions;
    }

    /**
     * Get the current context of the scan.
     *
     * @param null|string $match To check whether the current status is this value
     *
     * @return string|bool
     */
    protected function status($match = null)
    {
        $status = isset($this->status[0]) ? $this->status[0] : null;

        if ($match !== null) {
            return $status === $match;
        }

        return $status;
    }

    /**
     * Add a new status to the stack.
     *
     * @param string $status
     */
    protected function downStatus($status)
    {
        array_unshift($this->status, $status);
    }

    /**
     * Removes and return the current status.
     *
     * @return string|null
     */
    protected function upStatus()
    {
        return array_shift($this->status);
    }

    /**
     * Prepares the arguments found in functions.
     *
     * @param string $argument
     *
     * @return string
     */
    protected static function prepareArgument($argument)
    {
        if ($argument && ($argument[0] === '"' || $argument[0] === "'")) {
            return substr($argument, 1, -1);
        }
    }
}
