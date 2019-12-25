<?php

namespace GoLearningZone;

class Validator 
{
    static public function validate( $data, $livr ) 
    {
        \Validator\LIVR::defaultAutoTrim(true);

        $validator = new \Validator\LIVR($livr);

        $validated = $validator->validate($data);

        if (!is_array($validated) ) {
            throw new X([ 
                'type' => 'FORMAT_ERROR', 
                'fields' => $validator->getErrors()
            ]);
        }

        return $validated;
    }
}