<?php

trait program_paramoptions
{
	public function add_program_paramoptions(&$paramoptions)
	{
		$paramoptions[] = new rb_param_option('programid', 'prog.id', 'prog');
	}
}
