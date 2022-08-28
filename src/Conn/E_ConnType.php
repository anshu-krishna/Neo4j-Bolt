<?php
namespace Krishna\Neo4j\Conn;

enum E_ConnType: string {
	case Socket = Socket::class;
	case StreamSocket = StreamSocket::class;
}