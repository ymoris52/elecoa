<?php
    abstract class ObjectiveBase {
        protected $context;
        protected $strID;
        protected $cmdTable;

        protected $ignoreTrace;

        function __construct(&$ctx, $id, $node, $res, $sgo) {
            $this->context = $ctx;
            $this->strID = $id;
            $this->cmdTable = array();
        }

        public final function getContext() {
            return $this->context;
        }

        public final function getID() {
            return $this->strID;
        }

        public final function getType() {
            return 'Objective';
        }

        public abstract function terminate();

        public abstract function getValue();

        public abstract function setValue($value);

        /**
         * トレースオブジェクトから可読性の高い文字列を生成して返す。
         *
         * @param array $trace トレースオブジェクト。
         * @return string トレース文字列。
         */
        private function getTraceString($trace) {
            $result = '';

            if (isset($trace['file'])) {
                $result .= $trace['file'] . ' ';
            }
            if (isset($trace['line'])) {
                $result .= '(' . $trace['line'] . ') ';
            }
            if (isset($trace['object'])) {
                $result .= get_class($trace['object']);

                if ($trace['object'] instanceof ActivityBase) {
                    $result .= '<' . $trace['object']->getID() . '>';
                }
            }
            if (isset($trace['type'])) {
                $result .= $trace['type'];
            }
            if (isset($trace['class'])) {
                $result .= $trace['class'] . '::';
            }
            if (isset($trace['function'])) {
                $result .= $trace['function'];
            }
            if (isset($trace['args'])) {
                $result .= '(';
                $is_first = TRUE;
                foreach ($trace['args'] as $arg) {
                    if (!$is_first) {
                        $result .= ', ';
                    }

                    if (is_object($arg)) {
                        $result .= '[object]';
                    }
                    else if (is_array($arg)) {
                        $result .= '[array]';
                    }
                    else if (is_string($arg)) {
                        $result .= "'$arg'";
                    }
                    else if (is_bool($arg)) {
                        $result .= $arg ? 'true' : 'false';
                    }
                    else if (is_null($arg)) {
                        $result .= 'null';
                    }
                    else {
                        $result .= $arg;
                    }

                    $is_first = FALSE;
                }
                $result .= ')';
            }

            return $result;
        }

        public function callFromActivity($activityId, $cmd, $val) {
            $this->co_trace();
            if (array_key_exists($cmd, $this->cmdTable)) {
                $method = $this->cmdTable[$cmd]['Func'];
                $this->$method($activityId, $val);
            }
        }

        public function ignoreTrace() {
            $this->ignoreTrace = TRUE;
        }

        /**
         * ./syslog/traceに情報を出力する。
         *
         * @param string $str 出力文字列。指定しないと呼び出しメソッド（関数）情報を出力する。
         * @param bool $output_backtrace TRUEを指定するとバックトレース情報を出力する。
         * @param bool $force_output TRUEを指定すると出力しないモードの場合でも強制的に出力する。
         */
        protected function co_trace($str = '', $output_backtrace = FALSE, $force_output = FALSE) {
            if ($force_output && !$this->ignoreTrace) {
                // 毎回 $trace_file を作るので効率が悪い

                $dirs = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
                array_pop($dirs);
                $trace_file = implode('/', $dirs) . '/syslog/trace';
                $bt = debug_backtrace();
                if ($str === '' && isset($bt[1])) {
                    $str = $this->getTraceString($bt[1]);
                }
                if ($fh = fopen($trace_file, 'a+')) {
                    fwrite($fh, date('Y/m/d H:i:s') . " $str  [ " . $this -> getID() . ' / ' . get_class($this) . " ]\n");
                    if ($output_backtrace) {
                        foreach ($bt as $trace) {
                            fwrite($fh, $this->getTraceString($trace) . "\n");
                        }
                    }
                    fclose($fh);
                }
            }
        }
    }
