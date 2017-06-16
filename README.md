# proxy-munger

VERSION: 0.0

Proxy Toolkit in PHP.  This software is a collection of classes
and subroutines for implementing single-threaded event-loop based
proxies.  A single process can manage handle multiple proxies and
connection forwarders.

It has two I/O implementations:

- socket i/o : based on the PHP (optional) socket extension.  It
  supposed to provide a low overhead API.
- stream i/o : is the default/built-in PHP stream model.  It supports
  a number of features, specifically SSL.

In general, use socket i/o if you need high performance connections.
Us stream i/o only if you need SSL compatibility.

## Set-up



## Directory structure


## Configuring

## Dependencies

## TODO

* Config.php with functions for setting up proxies

## Changes

* 0.1:
  - 
* 0.0: first (single file) version


sshc -> [munger](httpTunnelClient,PROXY-POST) -> [corp-proxy] ->
		[munger:80](router)/HttpTunnelServer(POST) -> sshd?
			  /redir to https

sshc ->
  [munger](socket-fwd) ->
    [munger](httmTunnelClient,SSL-POST) -> 
      [munger](httpTunnelClient,CONNECT) ->
	[corp-proxy] ->
	  [munger:443](router+ssl)
	    /HttpTunnelServer(POST) -> sshd
	    /reverse-proxy	    


KPN -> [munger:80](router)/HttpTunelServer(POST)
			  /redir to https
       [munger:443](router+ssl)/HttpTunnelServer(POST)
			  /reverse-proxy
		      [munger:443](rev proxy) -> ???
		      [munger:443](proxy_tunnel) -> sshd
