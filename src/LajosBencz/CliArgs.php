<?php

namespace LajosBencz;


use ArrayAccess;

class CliArgs implements ArrayAccess
{
    /**
     * Prefix for flag names
     * @var string
     */
    public static $PREFIX_FLAG = '-';
    /**
     * Prefix for option names
     * @var string
     */
    public static $PREFIX_OPTION = '--';
    /**
     * Parse string into parameters array
     * @param string $string
     * @return array
     */
    public static function parseString(string $string): array
    {
        $e = 0;
        $q1 = false;
        $q2 = false;
        $q = false;
        $a = [];
        $f = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $c = $string[$i];
            if ($c === '\\') {
                $e++;
            }
            if ($e % 2 == 0) {
                if ($c === "'" && !$q2) {
                    $q1 = !$q1;
                    $q = true;
                } elseif ($c === '"' && !$q1) {
                    $q2 = !$q2;
                    $q = true;
                }
            }
            if (!$q) {
                if ($c === ' ' && !$q1 && !$q2 && $e % 2 == 0) {
                    if (strlen($f) > 0) $a[] = $f;
                    $f = '';
                } else {
                    if ($c !== '\\' || $e % 2 === 0) {
                        $f .= $c;
                    }
                }
            }
            if ($c !== '\\') {
                $e = 0;
            }
            $q = false;
        }
        if (strlen($f) > 0) $a[] = $f;
        return $a;
    }
    /**
     * --flag
     * @var bool[]
     */
    protected $_flags = [];
    /**
     * -option <value>
     * -option=<value>
     * @var string[]
     */
    protected $_options = [];
    /**
     * <argument>
     * @var string[]
     */
    protected $_arguments = [];
    /**
     * Initialize from numerically indexed array
     * @param null|string|array $params
     */
    public function __construct($params = null)
    {
        if (is_array($params)) {
            $this->fromArray($params);
        } else if (is_string($params)) {
            $this->fromString($params);
        } else if (is_object($params) && $params instanceof self) {
            $this->fromParameters($params);
        }
    }
    /**
     * Implementation for ArrayAccess, operates on options
     * @param mixed $option
     * @param mixed $value
     */
    public function offsetSet($option, $value)
    {
        $this->setOption($option, $value);
    }
    /**
     * Implementation for ArrayAccess, operates on options
     * @param mixed $option
     * @return null|string
     */
    public function offsetGet($option)
    {
        return $this->getOption($option);
    }
    /**
     * Implementation for ArrayAccess, operates on options
     * @param mixed $option
     * @return bool
     */
    public function offsetUnset($option)
    {
        return $this->removeOption($option);
    }
    /**
     * Implementation for ArrayAccess, operates on options
     * @param mixed $option
     * @return bool
     */
    public function offsetExists($option)
    {
        return $this->hasOption($option);
    }
    /**
     * Initialize from string
     * @param string $string
     * @param bool $clear (optional)
     */
    public function fromString(string $string, bool $clear = true): void
    {
        if ($clear) $this->clear();
        $this->fromArray(self::parseString($string));
    }
    /**
     * Initialize from numerically indexed array
     * @param array $params
     * @param bool $clear (optional)
     */
    public function fromArray(array $params, bool $clear = true): void
    {
        if ($clear) $this->clear();
        $this->_arguments = [];
        $this->_options = [];
        $this->_flags = [];
        $option = false;
        $fl = strlen(self::$PREFIX_FLAG);
        $ol = strlen(self::$PREFIX_OPTION);
        foreach ($params as $k => $v) {
            if ($option) {
                $this->setOption($option, $v);
                $option = false;
                continue;
            }
            $fi = substr($v, 0, $fl);
            $oi = substr($v, 0, $ol);
            if ($fi === self::$PREFIX_FLAG && ($oi !== self::$PREFIX_OPTION || $fl > $ol)) {
                $this->setFlag(substr($v, $fl));
                $option = false;
                continue;
            }
            if ($oi === self::$PREFIX_OPTION && ($fi !== self::$PREFIX_FLAG || $ol > $fl)) {
                $option = substr($v, $ol);
                if (preg_match('/^([^"\']+)=(.*)$/i', $option, $om)) {
                    $this->setOption($om[1], $om[2]);
                    $option = false;
                }
                continue;
            }
            $this->addArgument($v);
        }
        if ($option) {
            $this->setOption($option, true);
        }
    }
    /**
     * Copies from another CliParams
     * @param self $params
     * @param bool $clear (optional)
     */
    public function fromParameters(self $params, bool $clear = true): void
    {
        if ($clear) $this->clear();
        $a = $params->toArray();
        $this->fromArray($a);
    }
    /**
     * Remove every argument
     * @return self
     */
    public function removeArguments(): self
    {
        $this->_arguments = [];
        return $this;
    }
    /**
     * Remove every flag
     * @return self
     */
    public function removeFlags(): self
    {
        $this->_flags = [];
        return $this;
    }
    /**
     * Remove every option
     * @return self
     */
    public function removeOptions(): self
    {
        $this->_options = [];
        return $this;
    }
    /**
     * Clear everything
     * @return $this
     */
    public function clear(): self
    {
        $this->removeArguments();
        $this->removeFlags();
        $this->removeOptions();
        return $this;
    }
    /**
     * Is individual flag set, alias for hasFlag
     * @param string|array $key
     * @return bool
     */
    public function getFlag($key): bool
    {
        return $this->hasFlag($key);
    }
    /**
     * Is individual flag set
     * @param string|array $key
     * @return bool
     */
    public function hasFlag($key): bool
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($this->hasFlag($k)) {
                    return true;
                }
            }
            return false;
        }
        return array_key_exists($key, $this->_flags);
    }
    /**
     * Set/clear individual flag
     * @param string $key
     * @param bool $on (optional)
     * @return self
     */
    public function setFlag(string $key, bool $on = true): self
    {
        if ($on) {
            $this->_flags[$key] = true;
        } else {
            unset($this->_flags[$key]);
        }
        return $this;
    }
    /**
     * Toggle individual flag
     * @param string $key
     * @return bool
     */
    public function toggleFlag(string $key): bool
    {
        $h = $this->getFlag($key);
        $this->setFlag($key, !$h);
        return $h;
    }
    /**
     * Removes flag and returns if there has been such, alias for setFlag($key, false)
     * @param string|array $key
     * @return bool
     */
    public function removeFlag($key): bool
    {
        if (is_array($key)) {
            $result = false;
            foreach ($key as $k) {
                if ($this->removeFlag($k)) {
                    $result = true;
                }
            }
            return $result;
        }
        if ($this->hasFlag($key)) {
            $this->setFlag($key, false);
            return true;
        }
        return false;
    }
    /**
     * Clear and set flags from numerically indexed array
     * @param string[] $flags
     * @return self
     */
    public function setFlags(array $flags): self
    {
        $this->_flags = [];
        foreach ($flags as $f) {
            $this->_flags[$f] = true;
        }
        return $this;
    }
    /**
     * Get all flags as numerically indexed array
     * @return string[]
     */
    public function getFlags(): array
    {
        return array_keys($this->_flags);
    }
    /**
     * How many flags are there
     * @return int
     */
    public function countFlags(): int
    {
        return count($this->_flags);
    }
    /**
     * Is there such an option set
     * @param string|array $key
     * @return bool
     */
    public function hasOption($key): bool
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($this->hasOption($k)) {
                    return true;
                }
            }
            return false;
        }
        return array_key_exists($key, $this->_options);
    }
    /**
     * Set value of option
     * @param string $key
     * @param mixed $value (optional)
     * @return self
     */
    public function setOption(string $key, $value = true): self
    {
        $this->_options[$key] = $value;
        return $this;
    }
    /**
     * Get value of option, can supply default value
     * @param string|array $key
     * @param mixed $default (optional)
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($this->hasOption($k)) {
                    return $this->getOption($k);
                }
            }
            return $default;
        }
        if (!$this->hasOption($key)) {
            return $default;
        }
        return $this->_options[$key];
    }
    /**
     * Remove an option from the list, returns if there has been such
     * @param string|array $key
     * @return bool
     */
    public function removeOption($key): bool
    {
        if (is_array($key)) {
            $result = false;
            foreach ($key as $k) {
                if ($this->removeOption($k)) {
                    $result = true;
                }
            }
            return $result;
        }
        if ($this->hasOption($key)) {
            unset($this->_options[$key]);
            return true;
        }
        return false;
    }
    /**
     * Clear and set options from associative array
     * @param string[] $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->_options = $options;
        return $this;
    }
    /**
     * Get associative array of al options
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->_options;
    }
    /**
     * How many options are there
     * @return int
     */
    public function countOptions(): int
    {
        return count($this->_options);
    }
    /**
     * Is there such an argument in the list
     * @param string $argument
     * @return bool
     */
    public function hasArgument(string $argument): bool
    {
        return in_array($argument, $this->_arguments);
    }
    /**
     * Push new argument to the list
     * @param string $argument
     * @return self
     */
    public function addArgument(string $argument): self
    {
        $this->_arguments[] = $argument;
        return $this;
    }
    /**
     * Remove an argument from the list, returns number of occurrence
     * @param string $argument Value of argument
     * @param bool $all (optional) Remove only the first, or every occurrence
     * @return int
     */
    public function removeArgument(string $argument, bool $all = false): int
    {
        $r = 0;
        do {
            $i = array_search($argument, $this->_arguments, true);
            if ($i !== false && $i !== null) {
                $r++;
                unset($this->_arguments[$i]);
            }
            if (!$all) {
                break;
            }
        } while ($i !== false && $i !== null);
        return $r;
    }
    /**
     * Clear and set arguments from numerically indexed array
     * @param string[] $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        $this->_arguments = $arguments;
        return $this;
    }
    /**
     * Get Nth argument
     * @param int $index (optional)
     * @param mixed $default (optional)
     * @return mixed
     */
    public function getArgument(int $index = 0, $default = null)
    {
        if ($this->countArguments() <= $index) {
            return $default;
        }
        return $this->_arguments[$index];
    }
    /**
     * Get each argument as a numerically indexed array
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->_arguments;
    }
    /**
     * How many arguments are there
     * @return int
     */
    public function countArguments(): int
    {
        return count($this->_arguments);
    }
    /**
     * Return everything as an indexed array.
     * Order is arguments, flags, options
     * @return array
     */
    public function __toArray()
    {
        return $this->toArray();
    }
    /**
     * Return everything as an indexed array.
     * Order is arguments, flags, options
     * @param null|false|string $escape (optional)
     * @return array
     */
    public function toArray($escape = '"'): array
    {
        $r = [];
        foreach ($this->_flags as $f => $v) {
            if ($v) $r[] = self::$PREFIX_FLAG . $f;
        }
        foreach ($this->_options as $o => $v) {
            $v = str_replace(['"', "'"], ['\"', "\\'"], $v);
            if (strpos($v, ' ') !== false) {
                if (!$escape) $v = str_replace(' ', '\ ', $v);
                else $v = $escape . $v . $escape;
            }
            $r[] = self::$PREFIX_OPTION . $o . '=' . $v;
        }
        foreach ($this->_arguments as $a) {
            $r[] = $a;
        }
        return $r;
    }
    /**
     * Return everything as a string.
     * Order is arguments, flags, options
     * @param null|false|string $escape (optional)
     * @return string
     */
    public function toString($escape = '"'): string
    {
        $r = '';
        $a = $this->toArray($escape);
        foreach ($a as $i) {
            $r .= ' ' . $i;
        }
        return trim($r);
    }
    /**
     * Return everything as a string.
     * Order is arguments, flags, options
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
