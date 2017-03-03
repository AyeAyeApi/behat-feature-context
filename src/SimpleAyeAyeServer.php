<?php
/**
 * SimpleAyeAyeServer.php
 * @author    Daniel Mason <daniel@ayeayeapi.com>
 * @copyright (c) 2016 Daniel Mason <daniel@ayeayeapi.com>
 * @license   MIT
 * @see       https://github.com/AyeAyeApi/behat-feature-context
 */


namespace AyeAye\Behat;

class SimpleAyeAyeServer
{

    const STDIN = 0;
    const STDOUT = 1;
    const STDERR = 2;

    /**
     * @var resource
     */
    protected $process;

    /**
     * @var []
     */
    protected $pipes;

    /**
     * SimpleAyeAyeServer constructor.
     * @param string $docRoot
     * @param string[] $env
     */
    public function __construct($docRoot, array $env = [])
    {
        $docRoot = realpath($docRoot);

        $descriptorSpec = [
            static::STDIN  => ["pipe", "r"],
            static::STDOUT => ["pipe", "w"],
            static::STDERR => ["pipe", "w"],
        ];
        $pipes = [];

        $this->process = proc_open("php -S localhost:8000 $docRoot/index.php", $descriptorSpec, $pipes, $docRoot, $env);

        // Give it a second and see if it worked
        sleep(1);
        $status = proc_get_status($this->process);
        if (!$status['running']) {
            throw new \RuntimeException('Server failed to start: '.stream_get_contents($pipes[static::STDERR]));
        }
    }

    /**
     * Deconstructor
     */
    public function __destruct()
    {
        $status = proc_get_status($this->process);
        $parentPid = (int)$status['pid'];
        $pids = preg_split('/\s+/', `ps -o pid -p $parentPid | tail -n +2`);
        foreach ($pids as $pid) {
            if (is_numeric($pid)) {
                posix_kill($pid, 9); //9 is the SIGKILL signal
            }
        }
        posix_kill($status['pid'], SIGKILL);
        proc_terminate($this->process, SIGKILL);
    }
}
