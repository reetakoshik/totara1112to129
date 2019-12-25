<?php

namespace GoLearningZone\Actions;

abstract class Base
{
    public static function create()
    {
        return new static;
    }

    public function run(array $params = [])
    {
        try {
            $validated = $this->validate($params);
            $result = $this->execute($validated);
        } catch (\GoLearningZone\X $e) {
            return $this->renderJson($e->getError());
        } catch (\Exception $e) {
            return $this->renderJson(['Message' => $e->getMessage()]);
        }
        return $this->renderJson($result);
    }

    public function renderJson($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    abstract protected function validate($name);
    abstract protected function execute($name);
}