/*----------------------------------------------------------------------------\
|        Menu pour le calcul des jours de cativite d'Ingrid                   |
|-----------------------------------------------------------------------------|
|                                                                             |
|-----------------------------------------------------------------------------|
|                                                                             |
\----------------------------------------------------------------------------*/


Auj = new Date;
TempAuj = Auj.getTime();
StartDate = new Date(2002, 1, 23);
TempStart = StartDate.getTime();
DiffSec = Math.floor((TempAuj-TempStart)/1000);
DiffMin = Math.floor(DiffSec/60);
Diffheure = Math.floor(DiffMin/60);
DiffJour = Math.floor(Diffheure/24);

