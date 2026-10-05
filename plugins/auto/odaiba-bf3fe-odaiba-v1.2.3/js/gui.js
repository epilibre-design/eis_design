/********************************
 *  GUI: interface utilisateur
 *
 ********************************/
 

$(document).ready(function(){

	// print
	$('.print a').click(function() {
		window.print();
		return false;
	});

	// Lien en spip_out s'ouvre ds une nouvelle fenetre
	$('a.spip_out,a[rel*=external]').attr('target','_blank').attr('rel','external noopener noreferrer');


	// menu Hamburger
 	var burgerMenu = function() {

		$('.js-odaiba-nav-toggle').on('click', function(event){
			event.preventDefault();
			var $this = $(this);

			if ($('body').hasClass('offcanvas')) {
				$this.removeClass('active');
				$('body').removeClass('offcanvas');
			} else {
				$this.addClass('active');
				$('body').addClass('offcanvas');
			}
		});
	};
	burgerMenu();

	// Click outside of offcanvass
	var mobileMenuOutsideClick = function() {

		$(document).click(function (e) {
			var container = $(".odaiba-aside, .js-odaiba-nav-toggle");
			if (!container.is(e.target) && container.has(e.target).length === 0) {
				if ( $('body').hasClass('offcanvas') ) {
					$('body').removeClass('offcanvas');
					$('.js-odaiba-nav-toggle').removeClass('active');
				}
			}
		});


		// si le menu lateral ne prend pas toute la hauteur
		// si on scroll, cela replie le menu
		if ($(".odaiba-main-menu").height() < window.innerHeight) {
			$(window).scroll(function(){
				if ( $('body').hasClass('offcanvas') ) {

					$('body').removeClass('offcanvas');
					$('.js-odaiba-nav-toggle').removeClass('active');

				}
			});
		}



	};
	mobileMenuOutsideClick();

	// Go to the top
	// http://www.jqueryscript.net/other/Minimal-Back-To-Top-Functionality-with-jQuery-CSS3.html
	$(window).scroll(function(event){
		var scroll = $(window).scrollTop();
		if (scroll >= 800) {
			$(".go-top").addClass("show");
		} else {
			$(".go-top").removeClass("show");
		}
	});

	$('.go-top a').click(function(){
		$('html, body').animate({
			scrollTop: 0
		}, 1000);
	});

	// scroll doux
	$('.smooth-scroll').click (function() {
		var target = $(this).attr('href');
		var hash = "#" + target.substring(target.indexOf('#')+1);
		$('html, body').animate({ scrollTop:$(hash).offset().top 	}, 600);
		return false;
	});



});

