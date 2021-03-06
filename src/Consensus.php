<?php

namespace BitWasp\Bitcoin\Node;

use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Chain\ParamsInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Node\Chain\BlockIndexInterface;

class Consensus implements ConsensusInterface
{

    /**
     * @var Math
     */
    private $math;

    /**
     * @var ParamsInterface
     */
    private $params;

    /**
     * @param Math $math
     * @param ParamsInterface $params
     */
    public function __construct(Math $math, ParamsInterface $params)
    {
        $this->math = $math;
        $this->params = $params;
    }

    /**
     * @return ParamsInterface
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param int $amount
     * @return bool
     */
    public function checkAmount($amount)
    {
        return $amount < 0 || $amount < ($this->params->maxMoney() * Amount::COIN);
    }

    /**
     * @param int $height
     * @return int|string
     */
    public function getSubsidy($height)
    {
        $halvings = $height / $this->params->subsidyHalvingInterval();
        if ($halvings >= 64) {
            return 0;
        }

        $subsidy = 50 * Amount::COIN;
        $subsidy = $subsidy >> $halvings;
        return $subsidy;
    }

    /**
     * @param BlockIndexInterface $prevIndex
     * @param int $timeFirstBlock
     * @return int
     */
    public function calculateNextWorkRequired(BlockIndexInterface $prevIndex, $timeFirstBlock)
    {
        $header = $prevIndex->getHeader();
        $math = $this->math;

        $timespan = $this->calculateWorkTimespan($timeFirstBlock, $header);

        $negative = false;
        $overflow = false;
        $target = $math->decodeCompact($header->getBits(), $negative, $overflow);
        $limit = $this->math->decodeCompact($this->params->powBitsLimit(), $negative, $overflow);
        $new = gmp_init(bcdiv(bcmul($target, $timespan), $this->params->powTargetTimespan()), 10);
        if ($math->cmp($new, $limit) > 0) {
            $new = $limit;
        }

        return gmp_strval($math->encodeCompact($new, false), 10);
    }

    /**
     * @param ChainStateInterface $chain
     * @param BlockIndexInterface $prevIndex
     * @return int|string
     */
    public function getWorkRequired(ChainStateInterface $chain, BlockIndexInterface $prevIndex)
    {
        $math = $this->math;

        if ($math->cmp($math->mod($math->add($prevIndex->getHeight(), 1), $this->params->powRetargetInterval()), 0) !== 0) {
            // No change in difficulty
            return $prevIndex->getHeader()->getBits()->getInt();
        }

        // Re-target
        $heightLastRetarget = $math->sub($prevIndex->getHeight(), $math->sub($this->params->powRetargetInterval(), 1));
        $lastTime = $chain->fetchAncestor($heightLastRetarget)->getHeader()->getTimestamp();
        return $this->calculateNextWorkRequired($prevIndex, $lastTime);
    }

    /**
     * @param $timeFirstBlock
     * @param BlockHeaderInterface $header
     * @return mixed
     */
    public function calculateWorkTimespan($timeFirstBlock, BlockHeaderInterface $header)
    {
        $timespan = $header->getTimestamp() - $timeFirstBlock;
        
        $lowest = $this->params->powTargetTimespan() / 4;
        $highest = $this->params->powTargetTimespan() * 4;

        if ($timespan < $lowest) {
            $timespan = $lowest;
        }

        if ($timespan > $highest) {
            $timespan = $highest;
        }
        
        return $timespan;
    }
}
