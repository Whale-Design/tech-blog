<?php
namespace Observer;

use SplObserver;
use SplSubject;

class LogintimeObserver implements SplObserver
{

    /**
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     * @param SplSubject $subject <p>
     * The <b>SplSubject</b> notifying the observer of an update.
     * </p>
     * @return string
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        echo "我是观察者1：记录用户登录时间及上次登录时间\r\n";
    }
}