<?php

namespace inc\Bank;


interface interfaceBank{


	// Token generation
	public function generationToken(Request $request): string;

//	public function createQuestionnaires($params): string;
//
//	public function updateQuestionnaires($params): string;


}