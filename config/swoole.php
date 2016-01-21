<?php
return array(
        'mode' => SWOOLE_PROCESS,
        'sock_type' => SWOOLE_SOCK_TCP,
        'swoole_setting' => array(
                'timeout' => 2.5,  //select and epoll_wait timeout.
                'poll_thread_num' => 1,  //reactor thread num
                'writer_num' => 1,       //writer thread num
                'worker_num' => 1,       //worker process num
                'backlog' => 128,        //listen backlog
                'open_cpu_affinity' => 1,
                'open_tcp_nodelay' => 1,
                'log_file' => '/tmp/swoole.log', 
                'daemonize' => 0, 
            ),
);
