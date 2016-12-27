<?php

namespace Hexogen\KDTree\Tests;

use Hexogen\KDTree\Item;
use Hexogen\KDTree\ItemList;
use Hexogen\KDTree\KDTree;
use Hexogen\KDTree\NodeInterface;
use \Mockery as m;

class KDTreeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldCreateAnInstance()
    {
        $itemList = new ItemList(5);
        $tree = new KDTree($itemList);

        $this->assertInstanceOf(KDTree::class, $tree);
    }

    /**
     * @test
     * @dataProvider itemProvider
     * @param ItemList $itemList
     */
    public function itShouldCreateTree(ItemList $itemList)
    {
        $tree = new KDTree($itemList);

        $this->checkTree($tree);
    }

    /**
     * @test
     */
    public function itShouldGetNumberOfDimensionsInItems()
    {
        $tree = new KDTree($this->getRandomItemsList(10, 1));
        $this->assertEquals(1, $tree->getDimensionCount());
        $tree = new KDTree($this->getRandomItemsList(10, 5));
        $this->assertEquals(5, $tree->getDimensionCount());
    }

    /**
     * @test
     */
    public function itShouldGetNumberOfItemsInTheTree()
    {
        $tree = new KDTree($this->getRandomItemsList(0));
        $this->assertEquals(0, $tree->getItemCount());
        $tree = new KDTree($this->getRandomItemsList(10, 5));
        $this->assertEquals(10, $tree->getItemCount());
    }

    /**
     * @test
     */
    public function itShouldGetMinBoundary()
    {
        $tree = new KDTree($this->getRandomItemsList(0));
        $this->assertEquals(INF, $tree->getMinBoundary()[0]);
        $this->assertEquals(INF, $tree->getMinBoundary()[1]);
        $tree = new KDTree($this->getRandomItemsList(5, 2, [
            [1.2, 2.2],
            [2.3, 2.4],
            [3.2, 2.1],
            [1.1, 2.0],
            [1.3, 2.2]
        ]));
        $this->assertEquals(1.1, $tree->getMinBoundary()[0]);
        $this->assertEquals(2.0, $tree->getMinBoundary()[1]);
    }

    /**
     * @test
     */
    public function itShouldGetMaxBoundary()
    {
        $tree = new KDTree($this->getRandomItemsList(0));
        $this->assertEquals(-INF, $tree->getMaxBoundary()[0]);
        $this->assertEquals(-INF, $tree->getMaxBoundary()[1]);
        $tree = new KDTree($this->getRandomItemsList(5, 2, [
            [1.2, 2.2],
            [2.3, 2.4],
            [3.2, 2.1],
            [1.1, 2.0],
            [1.3, 2.2]
        ]));
        $this->assertEquals(3.2, $tree->getMaxBoundary()[0]);
        $this->assertEquals(2.4, $tree->getMaxBoundary()[1]);
    }

    /**
     * item provider
     */
    public function itemProvider()
    {
        $lists = [];

        $params = [];
        $list = new ItemList(5);
        for ($id = 0, $i = -10.; $i < 10.; $i += .1, $id++) {
            $item = new Item($id, [$i, $i, $i, $i, $i]);
            $list->addItem($item);
        }
        $params[] = $list;
        $lists[] = $params;

        $params = [];
        $list = new ItemList(5);
        for ($id = 0, $i = 10.; $i > -10.; $i -= .1, $id++) {
            $item = new Item($id, [$i, $i, $i, $i, $i]);
            $list->addItem($item);
        }
        $params[] = $list;
        $lists[] = $params;


        $params = [];
        $list = new ItemList(5);
        for ($i = 0; $i < 100; $i++) {
            $item = new Item($id, [0, 0, 0, 0, 0]);
            $list->addItem($item);
        }
        $params[] = $list;
        $lists[] = $params;

        $params = [];
        $list = new ItemList(2);
        for ($i = 0; $i < 100; $i++) {
            if ($i % 2 == 0) {
                $item = new Item($i, [rand(-10, 10),rand(-10, 10)]);
            } else {
                $item = new Item($i, [2.,2.]);
            }

            $list->addItem($item);
        }
        $params[] = $list;
        $lists[] = $params;

        for ($i = 1; $i < 6; $i++) {
            $list = $this->getRandomItemsList(100, $i);
            $params[] = $list;
            $lists[] = $params;
        }

        return $lists;
    }

    private function checkTree(KDTree $tree)
    {
        $root = $tree->getRoot();
        $this->checkNode($root, 0);
    }

    private function checkLeftBranch(NodeInterface $node, float $value, int $d)
    {
        $val = $node->getItem()->getNthDimension($d);

        $this->assertLessThanOrEqual($value, $val);

        $left = $node->getLeft();
        if ($left) {
            $this->checkLeftBranch($left, $value, $d);
        }
        $right = $node->getRight();
        if ($left) {
            $this->checkLeftBranch($right, $value, $d);
        }
    }

    private function checkRightBranch(NodeInterface $node, float $value, int $d)
    {
        $val = $node->getItem()->getNthDimension($d);

        $this->assertGreaterThanOrEqual($value, $val);

        $left = $node->getLeft();
        if ($left) {
            $this->checkRightBranch($left, $value, $d);
        }
        $right = $node->getRight();
        if ($left) {
            $this->checkRightBranch($right, $value, $d);
        }
    }

    public function checkNode(NodeInterface $node, int $d)
    {
        $value = $node->getItem()->getNthDimension($d);
        $nextD = ($d + 1) % $node->getItem()->getDimensionsCount();
        $left = $node->getLeft();
        if ($left) {
            $this->checkLeftBranch($left, $value, $d);
            $this->checkNode($left, $nextD);
        }

        $right = $node->getRight();
        if ($left) {
            $this->checkRightBranch($right, $value, $d);
            $this->checkNode($right, $nextD);
        }
    }

    protected function getRandomItemsList(int $num = 100, int $dimensions = 2, array $coordinatesSet = [])
    {
        $list = new ItemList($dimensions);
        for ($i = 0; $i < $num; $i++) {
            if (empty($coordinatesSet)) {
                $coordinates = [];
                for ($j = 0; $j < $dimensions; $j++) {
                    $coordinates[] = rand(-10, 10);
                }
            } else {
                $coordinates = $coordinatesSet[$i];
            }
            $item = new Item($i, $coordinates);

            $list->addItem($item);
        }
        return $list;
    }
}
