<?php

class Validator {

    static public function validate( $data, $livr ) {

        \Validator\LIVR::defaultAutoTrim(true);
        $validator = new \Validator\LIVR( $livr );

        $validated = $validator->validate($data);

        if (!is_array($validated)) {
            throw new X([ 
                'Type' => 'FORMAT_ERROR', 
                'Fields' => $validator->getErrors()
            ]);    
        } 

        return $validated;
    }
}
