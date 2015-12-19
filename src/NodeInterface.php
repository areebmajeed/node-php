<?php

namespace BitWasp\Bitcoin\Node;

use BitWasp\Bitcoin\Networking\Messages\Block;
use BitWasp\Bitcoin\Networking\Messages\GetHeaders;
use BitWasp\Bitcoin\Networking\Messages\Headers;
use BitWasp\Bitcoin\Networking\Messages\Inv;
use BitWasp\Bitcoin\Networking\Messages\Ping;
use BitWasp\Bitcoin\Networking\Peer\Peer;
use BitWasp\Bitcoin\Node\Chain\Chains;
use BitWasp\Bitcoin\Node\Chain\ChainState;
use Evenement\EventEmitterInterface;

interface NodeInterface extends EventEmitterInterface
{
    public function start();
    public function stop();

    /**
     * @return ChainState
     */
    public function chain();

    /**
     * @return Chains
     */
    public function chains();

    public function onHeaders(Peer $peer, Headers $headers);
    public function onBlock(Peer $peer, Block $block);
    public function onInv(Peer $peer, Inv $inv);
    public function onGetHeaders(Peer $peer, GetHeaders $getHeaders);
    public function onPing(Peer $peer, Ping $ping);
}