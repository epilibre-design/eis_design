/*----------------------------------------------------------------------------\
|                       Grille de sudoku                        |
|-----------------------------------------------------------------------------|
|                          Created by Yvan Jolivet                          |
-----------------------------------------------------------------------------|
|                       |http://legrandhuit.jexiste.fr/
||                        |legrandhuit@wanadoo.fr
|-----------------------------------------------------------------------------|
|                   Copyright (c) Yvan Jolivet 2005                    |
|-----------------------------------------------------------------------------|
|                    Date de création : 25/04/2006 
\----------------------------------------------------------------------------*/



function debutscript()

{jeusol=0;

return;

}

function choixjeu(jeu)

{jeusol=jeu;

tabjeu.elements[jeusol].style.backgroundColor="aqua";

if (jeu!=jeuprec)

	{tabjeu.elements[jeuprec].style.backgroundColor="yellow"; jeuprec=jeusol};

for (i=0; i<9; i++)

	{tabsol.elements[i].style.backgroundColor="lime"};

if (jeusol<9) nolig=0;

else if (jeusol<18) nolig=1;

else if (jeusol<27) nolig=2;

else if (jeusol<36) nolig=3;

else if (jeusol<45) nolig=4;

else if (jeusol<54) nolig=5;

else if (jeusol<63) nolig=6;

else if (jeusol<72) nolig=7;

else nolig=8;

nocol=(jeusol-(nolig*9));

if (nolig<3) cary=0; else if (nolig<6) cary=3; else cary=6;

if (nocol<3) carx=0; else if (nocol<6) carx=3; else carx=6;

for (i=0; i<9; i++)

	{if (tbsolu[nolig][i] != 0) 

		{pris=tbsolu[nolig][i]; pris--; tabsol.elements[pris].style.backgroundColor="red"};

	};

for (i=0; i<9; i++)

	{if (tbsolu[i][nocol] != 0)

		{pris=tbsolu[i][nocol]; pris--;tabsol.elements[pris].style.backgroundColor="red"};

	};

maxx=(carx + 3);maxy=(cary + 3);

for (y=cary; y<maxy; y++)

	{for (x=carx; x<maxx; x++)

		{if (tbsolu[y][x] != 0)

			{pris=tbsolu[y][x]; pris--;tabsol.elements[pris].style.backgroundColor="red"};

		};



	};

return;

}

function choixsol(sol)

{tabjeu.elements[jeusol].value=sol;

tbsolu[nolig][nocol]=sol;

for (i=0; i<9; i++)

	{tabsol.elements[i].style.backgroundColor="lime"};

return;

}

function rabcase()

{tabjeu.elements[jeusol].value=" ";

tbsolu[nolig][nocol]=0;

return;

}

function rabligne()

{deblig=(nolig*9);finlig=(deblig+9);

for (i=0; i<9; i++) tbsolu[nolig][i]=0;

for (i=deblig; i<finlig; i++) tabjeu.elements[i].value=" ";

return;

}

function rabcolonne()

{debcol=nocol;fincol=(nocol+81);

for (i=0; i<9; i++) tbsolu[i][nocol]=0;

for (i=debcol; i<fincol; i=i+9) tabjeu.elements[i].value=" ";

return;

}

function rabcarre()

{for (y=cary; y<maxy; y++)

	{for (x=carx; x<maxx; x++)

		tbsolu[y][x]=0};

deblig=(carx+(cary*9));finlig=(deblig+3);

for (i=deblig; i<finlig; i++) tabjeu.elements[i].value=" ";

deblig=(deblig+9);finlig=(deblig+3);

for (i=deblig; i<finlig; i++) tabjeu.elements[i].value=" ";

deblig=(deblig+9);finlig=(deblig+3);

for (i=deblig; i<finlig; i++) tabjeu.elements[i].value=" ";

return;

}

function valider()

{topano=0;

for (i=0; i<9; i++)

	{cumsolu=0;

	 for (j=0; j<9; j++) {cumsolu=(cumsolu + tbsolu[i][j])};

	 if (cumsolu != 45) {topano=1; nolig=(i+1); alert("numéro de la ligne en anomalie : " + nolig)};

	};

for (i=0; i<9; i++)

	{cumsolu=0;

	 for (j=0; j<9; j++) {cumsolu=(cumsolu + tbsolu[j][i])};

	 if (cumsolu != 45) {topano=1; nocol=(i+1); alert("numéro de la colonne en anomalie : " + nocol)};

	};

for (k=0; k<9; k=k+3)

	{cumsolu=0;

	 for (i=0; i<3; i++)

		{for (j=(k+0); j<(k+3); j++) cumsolu=(cumsolu + tbsolu[i][j]);

		};

	 if (cumsolu != 45) {topano=1; nolig=(i-2);nocol=(j-2);alert("carré en anomalie pour ligne " + nolig + " et colonne " + nocol)};

	 cumsolu=0;

	 for (i=3; i<6; i++)

		{for (j=(k+0); j<(k+3); j++) cumsolu=(cumsolu + tbsolu[i][j]);

		};

	 if (cumsolu != 45) {topano=1; nolig=(i-2);nocol=(j-2);alert("carré en anomalie pour ligne " + nolig + " et colonne " + nocol)};

	 cumsolu=0;

	 for (i=6; i<9; i++)

		{for (j=(k+0); j<(k+3); j++) cumsolu=(cumsolu + tbsolu[i][j]);

		};

	 if (cumsolu != 45) {topano=1; nolig=(i-2);nocol=(j-2);alert("carré en anomalie pour ligne " + nolig + " et colonne " + nocol)};

	};

if (topano==0) alert("Bravo ! Grille OK");

return;

}

function kesaco()

{alert ("Le Sudoku, qu'est-ce encore ? C'est un jeu très récent en France. La grille contient 9 carrés de 9 cases. Chaque carré doit utiliser tous les chiffres de 1 à 9. De plus, chaque ligne horizontale et chaque colonne verticale doivent, elles aussi, utiliser tous les chiffres de 1 à 9.");

alert ("Ce script vous sert à concevoir des grilles. Quand celle-ci est validée vous pouvez alors créer votre Sudoku. Selon le niveau de difficulté souhaité vous pré-remplissez entre 20 et 40 cases sur une grille vierge. Ensuite le joueur adverse devra déduire les autres cases, avec patience et logique."); 

}

function modemploi()

{alert ("Pour vous positionner sur la grille : cliquez sur une case jaune, elle devient bleue. Pour y mettre un chiffre : sélectionnez-le dans la rangée chiffrée. Celle-ci vous affiche en vert les chiffres possibles, et en rouge les chiffres déjà utilisés dans le carré, la ligne et la colonne.");

alert ("Si vous souhaitez recommencer une partie de votre grille vous pouvez remettre à blanc une case, une ligne, une colonne ou un carré. Quand la grille est entièrement remplie validez-la. Si une erreur est détectée, un message vous en donnera l'origine. Bon courage.");  

alert ("D'autres jeux sur http://legrandhuit.jexiste.fr/");

} 

//variables

jeu=0;sol=0;jeusol=0;nolig=0;nocol=0;pris=0;carx=0;cary=0;deblig=0;finlig=0;jeuprec=0;cumsolu=0;topano=0;

//table des solutions

tbsolu = new Array();tbsolu[0] = new Array();

tbsolu[1] = new Array();tbsolu[2] = new Array();tbsolu[3] = new Array();tbsolu[4] = new Array();

tbsolu[5] = new Array();tbsolu[6] = new Array();tbsolu[7] = new Array();tbsolu[8] = new Array();

tbsolu[0][0] = 0;tbsolu[0][1] = 0;tbsolu[0][2] = 0;tbsolu[0][3] = 0;tbsolu[0][4] = 0;tbsolu[0][5] = 0;tbsolu[0][6] = 0;tbsolu[0][7] = 0;tbsolu[0][8] = 0;

tbsolu[1][0] = 0;tbsolu[1][1] = 0;tbsolu[1][2] = 0;tbsolu[1][3] = 0;tbsolu[1][4] = 0;tbsolu[1][5] = 0;tbsolu[1][6] = 0;tbsolu[1][7] = 0;tbsolu[1][8] = 0;

tbsolu[2][0] = 0;tbsolu[2][1] = 0;tbsolu[2][2] = 0;tbsolu[2][3] = 0;tbsolu[2][4] = 0;tbsolu[2][5] = 0;tbsolu[2][6] = 0;tbsolu[2][7] = 0;tbsolu[2][8] = 0;

tbsolu[3][0] = 0;tbsolu[3][1] = 0;tbsolu[3][2] = 0;tbsolu[3][3] = 0;tbsolu[3][4] = 0;tbsolu[3][5] = 0;tbsolu[3][6] = 0;tbsolu[3][7] = 0;tbsolu[3][8] = 0;

tbsolu[4][0] = 0;tbsolu[4][1] = 0;tbsolu[4][2] = 0;tbsolu[4][3] = 0;tbsolu[4][4] = 0;tbsolu[4][5] = 0;tbsolu[4][6] = 0;tbsolu[4][7] = 0;tbsolu[4][8] = 0;

tbsolu[5][0] = 0;tbsolu[5][1] = 0;tbsolu[5][2] = 0;tbsolu[5][3] = 0;tbsolu[5][4] = 0;tbsolu[5][5] = 0;tbsolu[5][6] = 0;tbsolu[5][7] = 0;tbsolu[5][8] = 0;

tbsolu[6][0] = 0;tbsolu[6][1] = 0;tbsolu[6][2] = 0;tbsolu[6][3] = 0;tbsolu[6][4] = 0;tbsolu[6][5] = 0;tbsolu[6][6] = 0;tbsolu[6][7] = 0;tbsolu[6][8] = 0;

tbsolu[7][0] = 0;tbsolu[7][1] = 0;tbsolu[7][2] = 0;tbsolu[7][3] = 0;tbsolu[7][4] = 0;tbsolu[7][5] = 0;tbsolu[7][6] = 0;tbsolu[7][7] = 0;tbsolu[7][8] = 0;

tbsolu[8][0] = 0;tbsolu[8][1] = 0;tbsolu[8][2] = 0;tbsolu[8][3] = 0;tbsolu[8][4] = 0;tbsolu[8][5] = 0;tbsolu[8][6] = 0;tbsolu[8][7] = 0;tbsolu[8][8] = 0;

