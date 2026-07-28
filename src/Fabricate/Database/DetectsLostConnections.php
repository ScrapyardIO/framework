<?php

namespace Fabricate\Database;

use Throwable;

trait DetectsLostConnections
{
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * Stub until fabricate/database lands a full LostConnectionDetector.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByLostConnection(Throwable $e)
    {
        $message = $e->getMessage();

        foreach ([
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order',
            'Adaptive Server connection failed',
            'Connection not available',
            'Broken pipe',
            'connection is no longer usable',
        ] as $needle) {
            if ($needle !== '' && str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
