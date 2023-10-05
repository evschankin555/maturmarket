function getURLVar(key) {
	var value = [];

	var query = String(document.location).split('?');

	if (query[1]) {
		var part = query[1].split('&');

		for (i = 0; i < part.length; i++) {
			var data = part[i].split('=');

			if (data[0] && data[1]) {
				value[data[0]] = data[1];
			}
		}

		if (value[key]) {
			return value[key];
		} else {
			return '';
		}
	}
}

// Autocomplete */
(function($) {
	$.fn.autocomplete = function(option) {
		return this.each(function() {
			this.timer = null;
			this.items = new Array();
			var html,i,value;
			var track = 0;

			$.extend(this, option);

			$(this).attr('autocomplete', 'off');

			// Focus
			$(this).on('focus', function() {
				this.request();
			});

			// Blur
			$(this).on('blur', function() {
				setTimeout(function(object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$(this).on('keydown', function(event) {
				switch(event.keyCode) {
					case 27: // escape
						this.hide();
						break;
					default:
						this.request();
						break;
				}
			});

			// Click
			this.click = function(event) { 
				event.preventDefault();

				value = $(event.target).closest('li').attr('data-value');

				if (value && this.items[value]) {
					if (this.items[value]['type'] == 'search') { 
						window.location = this.items[value]['value']; 
					}else{
						this.select(this.items[value]);						
					}
				}
			}

			// Show
			this.show = function() {
				var pos = $(this).position();

				$(this).siblings('ul.dropdown-menu').css({
					top: pos.top + $(this).outerHeight(),
					left: pos.left
				});

				$(this).siblings('ul.dropdown-menu').show();
			}

			// Hide
			this.hide = function() {
				$(this).siblings('ul.dropdown-menu').hide();
			}

			// Request
			this.request = function() {
				clearTimeout(this.timer);

				this.timer = setTimeout(function(object) {
					object.source($(object).val(), $.proxy(object.response, object));
				}, 200, this);
			}

			// Response
			this.response = function(json) { 

				if (json.length && json[0]['type'] == 'search') {
					html = '<ul class="search__list">';

					if (json.length) {
						for (i = 0; i < json.length; i++) {
							this.items[json[i]['id']] = json[i];
						}

						for (i = 0; i < json.length; i++) {
							if (!json[i]['category']) {
								if (json[i]['image1']) {
									html += '<li data-value="' + json[i]['id'] +'"><a class="search__item" href="' + json[i]['value'] + '">';
									html += '<figure class="search__item-image"><img src="' + json[i]['image1'] + '" alt=""></figure>';
									html += '<p class="search__item-title">';
									if (json[i]['model']) {
										html += '<span class="search__item-model">' + json[i]['model'] + '</span>';
									}
									html += json[i]['label'];
									html += '</p>';
									if (json[i]['special']) { 
										html += '<p class="search__item-price"><b>' + json[i]['special'] +'</b><u>' + json[i]['price'] +'</u></p></a></li>';
									}else{
										html += '<p class="search__item-price"><b>' + json[i]['price'] +'</b></p></a></li>';
									}
									
								}else{
									html += '<li data-value="' + json[i]['value'] + '"><a href="#" class="search__item search__item--text">' + json[i]['label'] + '</a></li>';
								}
							}
						}

						
						// Get all the ones with a categories
						var category = new Array();

						for (i = 0; i < json.length; i++) {
							if (json[i]['category']) {
								if (!category[json[i]['category']]) {
									category[json[i]['category']] = new Array();
									category[json[i]['category']]['name'] = json[i]['category'];
									category[json[i]['category']]['item'] = new Array();
								}

								category[json[i]['category']]['item'].push(json[i]);
							}
						}

						for (i in category) {
							html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

							for (j = 0; j < category[i]['item'].length; j++) {
								html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
							}
						}
						html += '</ul>';
					}

					if (html) {
						//this.show
						$('html').addClass('is-search-keyup');
					} else {
						//this.hide();
						$('html').addClass('is-search-keyup');
					}

					$(this).siblings('div.search__dropdown').html(html);
				}else{

					html = '';

					if (json.length) {
						for (i = 0; i < json.length; i++) {
							this.items[json[i]['value']] = json[i];
						}

						for (i = 0; i < json.length; i++) {
							if (!json[i]['category']) {
								html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
							}
						}

						// Get all the ones with a categories
						var category = new Array();

						for (i = 0; i < json.length; i++) {
							if (json[i]['category']) {
								if (!category[json[i]['category']]) {
									category[json[i]['category']] = new Array();
									category[json[i]['category']]['name'] = json[i]['category'];
									category[json[i]['category']]['item'] = new Array();
								}

								category[json[i]['category']]['item'].push(json[i]);
							}
						}

						for (i in category) {
							html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

							for (j = 0; j < category[i]['item'].length; j++) {
								html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
							}
						}
					}

					if (html) {
						this.show();
					} else {
						this.hide();
					}

					$(this).siblings('ul.dropdown-menu').html(html);

				}

			}

			if ($(this).hasClass('js-search-input')){

				$(this).after('<div class="search__dropdown"></div>');
				$(this).siblings('div.search__dropdown').on('click', 'ul.search__list > li > a' , $.proxy(this.click, this));

			}else{

				$(this).after('<ul class="dropdown-menu"></ul>');
				$(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

			}				



		});
	}
})(window.jQuery);


	// --------------------------------------------------------------------------
	// Avoid dubbling element's ID in mobile contents
	// --------------------------------------------------------------------------

	function mobiheader() {
			$(document).on('click','.nav__mobile li', function(){
				if (window.innerWidth < 992 && $('.nav__mobile ul.nav__list-menu').is(':empty')) {

					$('.nav__mobile ul.nav__list-menu').html('<img src="catalog/view/theme/technics/images/loader.svg" alt="">');
					
					$('.nav__mobile ul.nav__list-menu').load('index.php?route=common/header&mobiheader=1', function(e) {fancyPopUp();});
				}
			});


			if ($('div.ctrl').length < 2) { return;}
			var contenttemp;
			contenttemp = $('div.ctrl').not(':empty').first().html();
			if (window.innerWidth < 768) {
				$('.topbar div.ctrl').empty();
				if ($('div.nav__mobile').siblings("div.ctrl").is(':empty')) {					
					$('div.nav__mobile').siblings("div.ctrl").html(contenttemp);
					fancyPopUp();
				}	
				var date = new Date(new Date().getTime() + 60 * 1000);
				document.cookie = "ismobile=1; path=/; expires=" + date.toUTCString();
			}else{
				$('div.nav__mobile').siblings("div.ctrl").empty();
				if ($('.topbar div.ctrl').is(':empty')) {					
					$('.topbar div.ctrl').html(contenttemp);
					fancyPopUp();
				}
				var date = new Date(new Date().getTime() + 60 * 1000);
				document.cookie = "ismobile=0; path=/; expires=" + date.toUTCString();				
			}
	}

	function onresize() {
		$(window).on('resize', function(){
			
			mobiheader();
			//nav();

		});
	}


	// --------------------------------------------------------------------------
	// Cookieagry
	// --------------------------------------------------------------------------
	
	function cookieagry() {
		$(document).on('click', '#cookieagry', function(e) {
			var date = new Date(new Date().getTime() + 1000 * 60 * 60 * 24 * 365);
			document.cookie = "cookieagry=1; path=/; expires=" + date.toUTCString();
			$('.cookieagry').hide();
		});
	}

	// --------------------------------------------------------------------------
	// Scroll to top
	// --------------------------------------------------------------------------
	
	function scrollToTop() {
		var $s = $('.js-stt');
		if ($s.length && window.innerWidth > 767) {
			$(window).scroll(function(){
				if ($(this).scrollTop() > 300){ 
					$s.addClass('active');
				} else {
					$s.removeClass('active');
				}
			});	
			$s.on('click', function(e){
				$('html, body').animate({
					scrollTop: 0
				}, 400);
				e.preventDefault();
			});
		}	
	}
	
	// --------------------------------------------------------------------------
	// Chats
	// --------------------------------------------------------------------------


	function chats() {

		$(document).on('click', '.app-chats__toggle', function (event) {
			event.preventDefault();

			if ($('html').is('.is-chats-open')) {

				$('html').removeClass('is-chats-open');
			} else {

				$('html').addClass('is-chats-open');
			}
		});

		$(document).on('click', function (e) {
			if ($(e.target).closest('.app-chats').length == 0) {
				$('html').removeClass('is-chats-open');
			}
		});
	}


	// --------------------------------------------------------------------------
	// SETS
	// --------------------------------------------------------------------------

	function technicsSet() {

		$(document).on('click','.js-set-chng-btn', function(e){
		  var product_id = $(this).attr('data-for');
		  var set_id = $(this).closest('.js-set-container').attr('data-for');
		  var popup = $('.js-popup-package');

		   $('#popup-set-click-content').empty();

			$('#popup-set-click-content').load('index.php?route=extension/module/technics_set/getvariants&setproduct_id='+product_id+'&set_id='+set_id,function(){
			});

		});


		$(document).on('click','.js-add-toset', function(e){
		  e.preventDefault();
		  var old_product_id = $(this).attr("data-for");
		  var set_id = $(this).closest('div.js-set-popup').attr("data-for");
		  var qty = $(this).attr("data-qty");
		  var elm = $('.js-set-container[data-for = "'+set_id+'"]').find('.js-products-in-set[data-for = "'+old_product_id+'"]'); 
		  var product_id = $(this).find('.js-set-item-add').attr("data-for");
		  var popup = $('.js-popup-package'); 
		  var proddata =  $('.set_product_data');

		  $('.set_product_data').each(function(indx, element){

				if ($(element).attr("data-for") == old_product_id) {
					$(element).attr("data-for",product_id);
					$(element).attr("name",'setproducts['+product_id+']');
					
				}
			});

		  elm.empty();


			elm.load('index.php?route=extension/module/technics_set/getproduct&setproduct_id='+product_id+'&qty='+qty,function(){
				fancyPopUp(); lazyLoad();slick('.js-slick-products');
			});

		  elm.attr("data-for",product_id);

		  setTimeout(function() { $.fancybox.close() }, 1000);

				var datas = $('.setdata-'+set_id+'').serialize();
				$.ajax({
					url: 'index.php?route=extension/module/technics_set/refreshtotal',
					type: 'post',
					data: datas,
					dataType: 'json',
					beforeSend: function() {
					},
					complete: function() {
					},
					success: function(json) {

						if (json['success']) {
							$('.setdata-'+set_id+' .js-set-total').html(json['success']['total']);
							$('.setdata-'+set_id+' .js-set-discount').html(json['success']['discount']);
						}
						
					},
					error: function(xhr, ajaxOptions, thrownError) {
						alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});



		});
		
		$(document).on('click', '.js-btn-add-set2cart', function(e) {
			e.preventDefault();
			$.ajax({
				url: 'index.php?route=checkout/cart/setadd',
				type: 'post',
				data: $(this).closest('.setdata').serialize(),
				dataType: 'json',
				beforeSend: function() {

				},
				complete: function() {
				},
				success: function(json) {

						setTimeout(function () {
							$('#cart-total').html('<mark class="cart__counter" id="cart-total"> ' + json['total'] + '</mark>');
						}, 100);

						cartExrtaElem(json['total']);
						
						if ($('.js-cart-call').length) {
							$('.js-cart-call>button').trigger('click');
						} else {
							$('.app').append($('<div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert"> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
						}
						
						sendMetrics('technics_settocart');
							
						$('#cart .cart__body').load('index.php?route=common/cart/info div.cart__scroll',function(){
							$('#cart input[type=\'number\']').styler();
						});
			
				}

			});
			return;
		});

	}

	// --------------------------------------------------------------------------
	// Add Subscribe footer
	// --------------------------------------------------------------------------
	function addSubscribe() {
		
		$('input[name=\'emailsubscr\']').keypress(function(e){ 
			if(e.which == 13){
				$(this).next().click();
			}
		});	
		
		$('.js-subscribe-btn').on('click', function(){
		var email = $('input[name="emailsubscr"]').val();

		$.ajax({
			url: 'index.php?route=extension/module/technics_subscribe/addsubscribe',
			type: 'post',
			dataType: 'json',
			data: 'email='+email+'&module=0',
			success: function (data) {
				$('.alert.alert-fixed').remove();
				if (data['error']) {
					$('.app').prepend($('<div class="alert alert-danger alert-fixed alert-dismissible fade show" role="alert"> ' + data['error'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
				}
				if (data['success']) {
					sendMetrics('technics_subscribe');
					$('.app').prepend($('<div class="alert alert-success alert-fixed alert-dismissible fade show" role="alert"> ' + data['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
					$('input[name=\'emailsubscr\']').val('');
					setTimeout(function(){
						$('.alert.alert-fixed').remove();
					}, 5000)
				}
			}
		}); 

		});
	}

	// --------------------------------------------------------------------------
	// products POP-UP view
	// --------------------------------------------------------------------------

	function fastCart() {
		$(document).delegate('.quickbuy-send','click', function(e) {
			if ($('#buy-click-type').val() == "category-popup") {
				var selector = 'popupprodid_'+$('#cat_prod_id').val();
				$('.fast-redirect').val(1);
				var $data = $('#popup-buy-click input,#popup-buy-click textarea,#'+selector+' input[type=\'text\'], #'+selector+' input[type=\'hidden\'], #'+selector+' input[type=\'radio\']:checked, #'+selector+' input[type=\'checkbox\']:checked, #'+selector+' select, #'+selector+' textarea');
			}else{
				var $data = $('#popup-buy-click input,#popup-buy-click textarea,  #product input[type=\'radio\']:checked, #product input[type=\'checkbox\']:checked, #product input[type=\'text\']:not([name=\'quantity\']), #product select, #product textarea');
			}

			cart.add2cartFast($data);			
		});	

		$(document).on('click', '.js-fancy-popup-cart', function(e) {
			var eventtag = 'technics_buyclick';
			sendMetrics(eventtag);
		});
	}

	// --------------------------------------------------------------------------
	// products POP-UP view
	// --------------------------------------------------------------------------
	function productsView() {

		$(document).on('click','.js-btn-preview', function(e){
		  var product_id = $(this).attr('data-for');
	      var path = '',
	        href = '',
	        manufacturer_id = '',
	        popuptype = '';
	      if($('#path_id').val()){
	        path = '&popuppath='+$('#path_id').val();
	      }
	      if($('#popuptype').val()){
	        popuptype = '&popuptype='+$('#popuptype').val();
	      }
	      if($('#manufacturer_id').val()){
	        manufacturer_id = '&manufacturer_id='+$('#manufacturer_id').val();
	      }
	      if($('#url').val()){
	        href = '&' + $('#url').val();
	      }
	      if($(this).attr('data-opt')){
	        href += '&optneed=1';
	      }

		  $('#popupprod').empty();
		  $('#popupprod').html('<img src="catalog/view/theme/technics/images/loader.svg" alt="">');

	      $('#popupprod').load('index.php?route=product/category/popupdetail'+path+popuptype+manufacturer_id+'&prod_id='+product_id+href,function(){
	      		activateElements();
	       });

		});

		$(document).on('click','#popupprod .other__item', function(e){
			e.preventDefault();
		  	var href = $(this).attr('href');
		  	if (href == '#'){return;}
	      	$('#popupprod').load(href,function(){
	      		activateElements();
	       	});
		});

	}

	// --------------------------------------------------------------------------
	// Activate JS scripts after loading new contents
	// --------------------------------------------------------------------------
	function activateElements() {
	    slick('.js-gallery');
	    slick('.js-slick-other');
	    fancyFastCart();
	    activateDatepicker();
	    activateUploadBtn();
	    formstyler();
	    fancyPopUp();
	    countdown();
	}


	// --------------------------------------------------------------------------
	// Activate JS datetimepicker after loading new contents
	// --------------------------------------------------------------------------
	function activateDatepicker() {	
		var localeCode = document.documentElement.lang,
			$d = $('.date'),
			$dt = $('.datetime'),
			$t = $('.time');
		if ($d.length) {
			$d.datetimepicker({
				locale: localeCode,
				format: 'L',
				icons: {
					previous: 'icon-datepicker icon-datepickerchevron-small-left',
					next: 'icon-datepicker icon-datepickerchevron-small-right'
				},
				pickTime: false
			});
		}
		if ($dt.length) {
			$dt.datetimepicker({
				locale: localeCode,
				icons: {
					time: 'icon-datepicker icon-datepickerclock',
					date: 'icon-datepicker icon-datepickercalendar',
					up: 'icon-datepicker icon-datepickerchevron-small-up',
					down: 'icon-datepicker icon-datepickerchevron-small-down',
					previous: 'icon-datepicker icon-datepickerchevron-small-left',
					next: 'icon-datepicker icon-datepickerchevron-small-right'
				},
				pickDate: true,
				pickTime: true
			});
		}
		if ($t.length) {
			$t.datetimepicker({
				locale: localeCode,
				format: 'LT',
				icons: {
					up: 'icon-datepicker icon-datepickerchevron-small-up',
					down: 'icon-datepicker icon-datepickerchevron-small-down'
				},
				pickDate: false
			});
		}
	}

	function activateUploadBtn() {	
		$('button[id^=\'button-upload\']').on('click', function() {
			var node = this;
			var timer;

			$('#form-upload').remove();

			$('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" /></form>');

			$('#form-upload input[name=\'file\']').trigger('click');

			if (typeof timer != 'undefined') {
		    	clearInterval(timer);
			}

			timer = setInterval(function() {
				if ($('#form-upload input[name=\'file\']').val() != '') {
					clearInterval(timer);

					$.ajax({
						url: 'index.php?route=tool/upload',
						type: 'post',
						dataType: 'json',
						data: new FormData($('#form-upload')[0]),
						cache: false,
						contentType: false,
						processData: false,
						beforeSend: function() {
							$(node).button('loading');
						},
						complete: function() {
							$(node).button('reset');
						},
						success: function(json) {
							$('.ui-error').remove();

							if (json['error']) {
								$(node).parent().find('input').after('<span class="error ui-error">' + json['error'] + '</span>');
							}

							if (json['success']) {
								alert(json['success']);

								$(node).parent().find('input').val(json['code']);
							}
						},
						error: function(xhr, ajaxOptions, thrownError) {
							alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
						}
					});
				}
			}, 500);
		});
	}
	
	// --------------------------------------------------------------------------
	// ocFilter. Button SHOW and CLEAR functional
	// --------------------------------------------------------------------------
	function doFilter() {
		$(document).on('click','#button-filter',function() {
			location = getOcFilterUrl();
		});
		$(document).on('click', '.filter__clear', function(e) {
			location = $('input[name=\'fix_filter_action\']').val();
		});		
	}

	function getOcFilterUrl() {
			var filter = [];

			$('input[name^=\'filter\']:checked').each(function(element) {
				filter.push(this.value);
			});
			var min_price = $('#min_price_current').val();
			var max_price = $('#max_price_current').val();

			var url = $('input[name=\'fix_filter_action\']').val() + '&filter=' + filter.join(',') + '&min_price=' + min_price + '&max_price=' + max_price ;
			return url ;
	}

	// --------------------------------------------------------------------------
	// ocFilter
	// --------------------------------------------------------------------------
	function sliderProducts(t) {
			$(".slidproducts").remove();
			$('#button-filter').removeAttr('disabled');
			var	filter = [];
			var $el = t.closest("label.ui-check");// Position of balun for checkbox
			if (t.hasClass("ui-range__slider")) {
				$el = t.closest(".filter__fieldset"); // Position of balun for priceslider
			}

			$('input[name^=\'filter\']:checked').each(function(element) {
				filter.push(this.value);
			});
			var min_price = $('#min_price_current').val();
			var max_price = $('#max_price_current').val();
			var cat_id = $('#filter_category_id').val();

		var url = getOcFilterUrl();
		$.ajax({
		  url: 'index.php?route=product/category/totalproducts/'+ '&filter=' + filter.join(',') + '&min_price=' + min_price + '&max_price=' + max_price   + '&filter_category_id=' + cat_id,
		  dataType: 'json',
		  success: function (json) {
		  var balun ;
		  if(json['total']){
			balun = '<span class="products-amount slidproducts" id="count-'+json['id']+'"><span class="products-amount__amount"> '+json['text_products']+' </span>'+ json['total']+'<a class="link-dashed" href="'+url+'">'+json['text_show']+'</a></span>';

		  }else{
			balun = '<span class="products-amount slidproducts" id="count-'+json['id']+'"><span class="products-amount__amount"> '+json['text_products']+' </span>'+ json['total'];
			$('#button-filter').attr('disabled','disabled');
		  }

		$el.after($(balun).fadeIn(100));	
						setTimeout(function(){
							$("#count-"+json['id']).fadeOut(100);
						}, 6000)
		  }
		});
	}



	// --------------------------------------------------------------------------
	// callBack
	// --------------------------------------------------------------------------
	function callBack() {
		$(document).on('click','.contact-send',function() {
				var success = 'false';
				$.ajax({
					url: 'index.php?route=extension/module/callback',
					type: 'post',
					data: $(this).closest('.data-callback').serialize() + '&action=send',
					dataType: 'json',
					beforeSend: function() {
						$('.data-callback > button').attr('disabled', 'disabled');
					},
					complete: function() {
						$('.data-callback > button').removeAttr('disabled');
					},
					success: function(json) {
						$('.alert, .ui-error, .icon-error').remove();
						$('.ui-group, .ui-field').removeClass('is-error');
						
						if (json['warning']) {
							if (json['warning']['name']) {
								$('.data-callback input[name=\'name\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['warning']['name'] + '</span>').parent().addClass('is-error');
							}
							if (json['warning']['phone']) {
								$('.data-callback input[name=\'phone\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['warning']['phone'] + '</span>').parent().addClass('is-error');
							}
							if (json['warning']['captcha']) {
								$('.writeus__heading').after('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['warning']['captcha'] + ' </div>');
									setTimeout(function(){
										$('.alert').remove();
									}, 5000)	
							}
						}
						if (json['success']){
							$('.writeus__heading').after('<div class="alert alert-success fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['success'] + ' </div>');
							
							success = 'true';
							
							sendMetrics('technics_callback');
							
							$('.data-callback input,.data-callback textarea').val('');
							
							setTimeout(function(){
								$.fancybox.close();	
							}, 3000)
						} 
					}

				});
		});
	}

	// --------------------------------------------------------------------------
	// Change products quantity In the Cart
	// --------------------------------------------------------------------------
	function cartChange() {

		$(document).on('change','.jq-number__field input', function(e){
			var qty = 0;
			var name;
			qty = $(this).val();
			name = $(this).attr('name');;

			if($(this).closest('div.sku__action').length > 0 || name == 'quantity') {return;} // If it is product or category page do nothing

			cart.update(name,qty);


		});

		$('#cartcontent .jq-number__field input').on('keydown', function(e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$('#cartcontent .jq-number__field input').change();
			}
		});

	}


	// --------------------------------------------------------------------------
	// Change Category View
	// --------------------------------------------------------------------------
	function setCatView(sub) {

//		$(document).on('click','.options__view .js-options-item', function(e){ 
//			e.preventDefault();

			var view = sub.attr("data-option");
/*
			var path = '';
			var type = 'product/category';
			if($('#path').val()){
				var path = "&path="+$('#path').val();
			}
			if($('#type').val()){
				var type = $('#type').val();
			}
			if(type == 'product/manufacturer'){
				cat_id = "/info&manufacturer_id="+$('#manufacturer_id').val()+"&view="+view;
			}
			if(type == 'product/special'){
				cat_id = "&view="+view;
			}
			if(type == 'product/search'){
				cat_id = "&view="+view;
			}
			if(type == 'product/category'){
				var cat_id = path+"&view="+view;
			}
			var href = window.location.search.substr(1);
			if(href){
				href ='&'+href;
			}
			if($('#url').val()){
				var href = $('#url').val();
			}
			
			if (localStorage.getItem('display') == view) {
				return;
			}
*/
//			localStorage.setItem('display', view);
		
			$('#mainContainer').load(window.location.href,{type: 'post',view: view},function(){
				lazyLoad();
				slick('.js-slick-products');
//				$('input[type=\'number\']').styler();
//				fancyPopUp();
//				countdown();
				activateElements();
				slickPlay();
//				$('.js-slick-products').slick('reInit');
			}); 
//		});	


	}
	
	// --------------------------------------------------------------------------
	// Loading checkoutStep
	// --------------------------------------------------------------------------
	
	function checkoutStep() {
		var step = $('.js-checkout');
		if (step.length) {
			var title = step.find('.checkout__accordion-btn'),
			inner = step.find('.checkout__accordion-content'),
			fix = $('.js-sticky').length ? $('.js-sticky').outerHeight() + 20 : 20;
			
			title.on('click', function(){
				if ($(this).closest(step).hasClass('pass')) {
					var thisStep = $(this).closest(step);
					thisStep.siblings(step).find(inner).slideUp(200);
					title.removeClass('is-open');
					thisStep.find(inner).slideDown({
						duration: 200,
						complete: function(){
							$('body, html').animate({scrollTop: thisStep.offset().top - fix});
							thisStep.find(title).addClass('is-open');
						}
					});
				}
			});

		}
	}
	
	
	// --------------------------------------------------------------------------
	// Loading fancyPopUp
	// --------------------------------------------------------------------------
	
	function fancyPopUp() {
		var $popup = $('.js-fancy-popup,.js-btn-preview');
		if ($popup.length) {
				$popup.fancybox({
					slideClass : 'popup-simple--fancybox', 
					btnTpl : {
						smallBtn   : '<button data-fancybox-close class="popup__close" title="{{CLOSE}}"><svg class="icon-close"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-close"></use></svg></button>'
					},
					autoFocus : false,
					scrolling   : 'no',
					hideScrollbar: true,
					closeClickOutside : true,
					touch : false
				});
		}
	}
		
	// --------------------------------------------------------------------------
	// Loading fancyFastCart
	// --------------------------------------------------------------------------
	
	function fancyFastCart() {
		var $popup = $('.js-fancy-popup-cart');
		if ($popup.length) {
				$popup.fancybox({
					slideClass : 'popup-simple--fancybox', 
					autoFocus : false,
					closeClickOutside : true,
					touch : false,
					beforeLoad: function( instance, slide ) {
						fastCartData(slide.opts.$orig);
					}
				});
		}
	}

	function fastCartData($objectClick) { 		
			var product_id = $objectClick.attr('data-for');
			var qty = $objectClick.closest(".sku__action").find('.jq-number__field input').val(); 
			var type = $objectClick.attr('data-typefrom');

			$('#cat_qty').val(qty);
			$('#cat_prod_id').val(product_id);
			$('#buy-click-type').val(type);
			if(type == "category-popup"){
				$('.fast-redirect').val(0);
				var selector = 'popupprodid_'+product_id;
				var $data = $('#'+selector+' input[type=\'text\'], #'+selector+' input[type=\'hidden\'], #'+selector+' input[type=\'radio\']:checked, #'+selector+' input[type=\'checkbox\']:checked, #'+selector+' select, #'+selector+' textarea');
			}
			if(type == "category-list"){
				$('.fast-redirect').val(1);
				var $data = $('#popup-buy-click input');
			}
			if(type == "cart-popup"){
				return;
			}
			if(type == "product"){ //console.log('test');
				var $data = $('#product input[type=\'text\'], #product input[type=\'number\'], #product input[type=\'hidden\'], #product input[type=\'radio\']:checked, #product input[type=\'checkbox\']:checked, #product select, #product textarea');
			}
			cart.add2cartFast($data);
	}
	
	// --------------------------------------------------------------------------
	// Loading button plugin (removed from BS4)
	// --------------------------------------------------------------------------
	
	function LBplugin() {
	  $.fn.button = function(action) {
		if (action === 'loading' && this.data('loading-text')) {
		  this.data('original-text', this.html()).html(this.data('loading-text')).prop('disabled', true);
		}
		if (action === 'reset' && this.data('original-text')) {
		  this.html(this.data('original-text')).prop('disabled', false);
		}
	  };
	}

	// --------------------------------------------------------------------------
	// TOOLTIPS ON HOVER
	// --------------------------------------------------------------------------
	
	function ocTooltip() {
		$('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
	}

	// --------------------------------------------------------------------------
	// Switch language & currency
	// --------------------------------------------------------------------------

	function currlanguage() {
		// Currency
		$('body').on('click','#form-currency .wallet__menu li', function(e) {
			e.preventDefault();

			$('#form-currency input[name=\'code\']').val($(this).attr('data-curr'));

			$('#form-currency').submit();
		});

		// Language
		$('body').on('click','#form-language .lang__menu li', function(e) {
			e.preventDefault();

			$('#form-language input[name=\'code\']').val($(this).attr('data-lang'));

			$('#form-language').submit();
		});
	}

	// --------------------------------------------------------------------------
	// Detect Touch
	// --------------------------------------------------------------------------

	function detectTouch() {
		if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
			$('html').addClass('touch-device');
		} else {
			$('html').addClass('no-touch-device');
		}
	}

	// --------------------------------------------------------------------------
	// Detect Vieport
	// --------------------------------------------------------------------------

/*
	function viewport() {

		var mvp = document.querySelector("meta[name=viewport]");

		if (screen.width <= 380) {

			mvp.setAttribute('content', 'width=380');
		} else {
			mvp.setAttribute('content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=3.0, shrink-to-fit=no');
		}

		$(window).on('resize', function (event) {

			if (screen.width <= 380) {

				mvp.setAttribute('content', 'width=380');
			} else {
				mvp.setAttribute('content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=3.0, shrink-to-fit=no');
			}
		});
	}
*/
	// --------------------------------------------------------------------------
	// Topbar
	// --------------------------------------------------------------------------

	function sticky() {}

	// $(window).on('load scroll', function(event) {

	// 	var offsetScroll = window.pageYOffset || document.documentElement.scrollTop;

	// 	if(offsetScroll >= 40 ) {
	// 		$('.topbar').addClass('is-sticky');
	// 	}
	// 	else {
	// 		$('.topbar').removeClass('is-sticky');
	// 	}

	// });

	// --------------------------------------------------------------------------
	// Nav
	// --------------------------------------------------------------------------

	function nav() {

//		$(window).on('load', function (event) {

			var nav = priorityNav.init({
				initClass: "js-priority", // Class that will be printed on html element to allow conditional css styling.
				mainNavWrapper: ".nav__priority", // mainnav wrapper selector (must be direct parent from mainNav)
				mainNav: "ul", // mainnav selector. (must be inline-block)
				navDropdownClassName: "nav__priority-dropdown", // class used for the dropdown - this is a class name, not a selector.
				navDropdownToggleClassName: "nav__priority-toggle", // class used for the dropdown toggle - this is a class name, not a selector.
				navDropdownLabel: $('.js-priority').attr('data-text-more'), // Text that is used for the dropdown toggle.
				navDropdownBreakpointLabel: "menu", //button label for navDropdownToggle when the breakPoint is reached.
				breakPoint: 50, //amount of pixels when all menu items should be moved to dropdown to simulate a mobile menu
				throttleDelay: 10, // this will throttle the calculating logic on resize because i'm a responsible dev.
				offsetPixels: 0, // increase to decrease the time it takes to move an item.
				count: true, // prints the amount of items are moved to the attribute data-count to style with css counter.

				//Callbacks
				moved: function moved() {}, // executed when item is moved to dropdown
				movedBack: function movedBack() {} // executed when item is moved back to main menu
			});
//		});

		// Reverse


		$(window).on('load resize', function (event) {

			$('.nav').find('li').removeClass('is-open');

			$('.nav').find('.nav__list-dropdown--full, .nav__dropdown--full').each(function (index) {
				var fullWidth = $(window).innerWidth(),
				    dropdown = $(this),
				    dropdownPosition = fullWidth - dropdown.offset().left - 40;

				dropdown.parent('li').addClass('is-full');
				dropdown.innerWidth(dropdownPosition);
				
//				if (screen.width > 991) {
//					$('.js-slick-nav').slick('refresh');
//				}
			});

			$('.nav').find('.nav__dropdown, .nav__list-dropdown').each(function (index) {
				var fullWidth = $(window).innerWidth(),
				    dropdown = $(this),
				    dropdownWidth = dropdown.innerWidth(),
				    dropdownPosition = dropdown.offset().left,
				    dropdownStatus = fullWidth - dropdownWidth - dropdownPosition;

				if (dropdownStatus < 0) {

					dropdown.parent('li').addClass('is-reverse');
				} else {
					dropdown.parent('li').removeClass('is-reverse');
				}
			});
		});

		// var touch = $('html').is('.touch-device');

		var isMob = $(window).innerWidth() < 992;

		if (isMob) {

			// Nav

			$(document).on('click', 'nav [class*="--arrow"]', function (event) {
				event.preventDefault();
				$('html').addClass('is-menu-open');
				if ($(this).closest('li').is('.is-open')) {
					$(this).closest('li').removeClass('is-open').closest('.nav__list-body').removeClass('is-overflow');
				} else {
					$('.nav__priority-dropdown-wrapper').removeClass('is-open');
					$(this).closest('li').siblings().removeClass('is-open').closest('.nav__list-dropdown').animate({scrollTop: 0}, 250);
					// $(this).closest('li').addClass('is-open').closest('.nav__list-body').addClass('is-overflow').scrollTop(0);

					$(this).closest('li').addClass('is-open').closest('.nav__list-body').animate({
						scrollTop: 0
					}, 250, function () {
						$(this).addClass('is-overflow');
					});
				}
			}).on('click', '.nav__list-back', function (event) {
				event.preventDefault();
				$(this).closest('li').removeClass('is-open').closest('.nav__list-body').removeClass('is-overflow');
			}).on('click', '.nav__list-close', function (event) {
				event.preventDefault();
				$(this).closest('li').removeClass('is-open').closest('.nav__list-body').removeClass('is-overflow');
				$('html').removeClass('is-menu-open');
			});

			// Nav MORE (Priority)


			$(document).on('click', '.nav__priority-toggle', function (event) {
				event.preventDefault();
				if ($(this).closest('.nav__priority-dropdown-wrapper').is('.is-open')) {
					$(this).closest('.nav__priority-dropdown-wrapper').removeClass('is-open');
				} else {
					$('.nav__menu, .nav__list').find('li').removeClass('is-open');
					$(this).closest('.nav__priority-dropdown-wrapper').addClass('is-open');
				}
			});
		} else {

			$('.nav__menu').on('mouseover', 'li', function (event) {
				$(this).addClass('is-open');
			}).on('mouseout', 'li', function (event) {
				$(this).removeClass('is-open');
			});

			$(document).on('mouseover', '.nav__priority-dropdown-wrapper', function (event) {
				$(this).addClass('is-open');
			}).on('mouseout', '.nav__priority-dropdown-wrapper', function (event) {
				$(this).removeClass('is-open');
			});

			$(document).on('mouseover', '.nav__priority-dropdown li', function (event) {
				$(this).addClass('is-open');
			}).on('mouseout', '.nav__priority-dropdown li', function (event) {
				$(this).removeClass('is-open');
			});
		}
	}


	// --------------------------------------------------------------------------
	// Search
	// --------------------------------------------------------------------------

	function search() {

		//$('.js-search-btn').on('click', function (event) {
		//	event.preventDefault();

		//	if ($(this).is('.is-active')) {
		//		$(this).removeClass('is-active').closest('.js-search').removeClass('is-open');
		//	} else {
		//		$(this).addClass('is-active').closest('.js-search').addClass('is-open');
		//	}
		//});

		$('.js-search').on('click', '.search__send', function (event) {

			var url = $('base').attr('href') + 'index.php?route=product/search';
			
			if (window.innerWidth < 768 && !$(this).closest('.header--v3,.header--v7').length > 0 ) {
				var value = $('nav.nav input[name=\'search\']').val();
			} else {
				var value = $('.topbar input[name=\'search\']').val();
			}
			
			if (value) {
				url += '&search=' + encodeURIComponent(value);
			}
			
			location = url;
		});
		
		$('.js-search').on('click', '.search__btn', function (event) {
			
			//event.preventDefault();

			//if ($(this).closest('.js-search').is('.is-open')) {

			//	$(this).closest('.js-search').removeClass('is-open');
			//} else {
			//	$(this).closest('.js-search').addClass('is-open');
			//}
			if ($(this).closest('.header--v7').length > 0) {
				event.preventDefault();
				$(this).closest('.js-search').addClass('is-open');
			}
			
			if ($(this).closest('.header--v1,.header--v4,.header--v6,.header--v8,.header--v10').length > 0) {
				if (matchMedia('only screen and (max-width: 1199px)').matches) {
					event.preventDefault();
					$(this).closest('.js-search').addClass('is-open');
				} else {
					$('.topbar .search__send').trigger('click');
				}
			}
			
			if ($(this).closest('.header--v5').length > 0) {
				if (matchMedia('only screen and (max-width: 767px)').matches) {
					event.preventDefault();
					$(this).closest('.js-search').addClass('is-open');
				} else {
					$('.topbar .search__send').trigger('click');
				}
			}

			
			if ($(this).closest('.header--v2,.header--v3,.header--v9').length > 0) {
				if (matchMedia('only screen and (min-width: 992px)').matches) {
					if (!$(this).closest('.js-search').is('.is-open')) {
						event.preventDefault();
						$(this).closest('.js-search').addClass('is-open');
					}
					
					$('.header--v2,.header--v3,.header--v9').each(function () {
					
						var searchWrapper = $(this).find('.search__wrapper'),
							colSearch = $(this).find('.col-search'),
							colNav = $(this).find('.col-nav'),
							colPhone = $(this).find('.col-phone'),
							colSearchWidth = colSearch.innerWidth(),
							colNavWidth = colNav.innerWidth(),
							colPhoneWidth = colPhone.innerWidth();

						searchWrapper.innerWidth(colSearchWidth + colNavWidth + colPhoneWidth - 15);
					
					});
				} else {
					event.preventDefault();
					$(this).closest('.js-search').addClass('is-open');
				}
			}
			
		if ($(this).closest('.js-search').is('.search')) {
			$('html').addClass('is-search-open');
		}

		}).on('click', '.search__close', function (event) {
			event.preventDefault();
			$('.js-search').removeClass('is-open');
			$('.js-search-input').val('');
			$('html').removeClass('is-search-keyup is-search-open');
		});

		$('.js-search-input').bind('keydown', function(e) {
			if (e.keyCode == 13) {
				$('.topbar .search__send').trigger('click');
			}
		});

		$('.js-search-input').autocomplete({
			'source': function(request, response) {
				$.ajax({
					url: 'index.php?route=product/product/autocomplete&filter_name=' +  encodeURIComponent(request),
					dataType: 'json',
					success: function(json) {
						response($.map(json, function(item) {
							return {
								type: 'search',
								label: item['name'],
								price: item['price'],
								model: item['model'],
								special: item['special'],
								image1: item['image'],
								value: item['href'],
								id: item['product_id']
							}
						}));

					}
				});
			},
			'select': function(item) { 
				$('#search_text').val(item['label']);
				window.location.href = item['href']; 
			}
		});


		$(document).on('click', function (e) {
			if ($(e.target).closest('.js-search').length == 0) {
				$('html').removeClass('is-search-keyup');
			}
		});

		// $(window).on('load resize', function(event) {


		// 	$('.header--v3, .header--v4').each(function() {


		// 		var searchWrapper    = $(this).find('.search__wrapper'),

		// 			colSearch = $(this).find('.col-search'),
		// 			colNav    = $(this).find('.col-nav'),
		// 			colSearchWidth = colSearch.innerWidth(),
		// 			colNavWidth    = colNav.innerWidth();


		// 			searchWrapper.innerWidth(colSearchWidth + colNavWidth - 15);


		// 	});


		// });

	}

	// --------------------------------------------------------------------------
	// Toggle
	// --------------------------------------------------------------------------

	function toggleBtn() {

		$(document).on('click', '.js-toggle .js-toggle-btn', function (event) {
			event.preventDefault();

			if ($(this).is('.is-active')) {
				$(this).removeClass('is-active').closest('.js-toggle').removeClass('is-open');
			} else {
				$('.js-toggle, .js-toggle-btn').removeClass('is-open is-active');
				$(this).addClass('is-active').closest('.js-toggle').addClass('is-open');
			}

			if ($(this).closest('.js-toggle').is('.cart')) {
				$('html').addClass('is-cart-open');
			}
		});

		$(document).on('click', '.js-toggle-close', function (event) {
			event.preventDefault();
			$('.js-toggle, .js-toggle-btn').removeClass('is-open is-active');
			$('html').removeClass('is-cart-open');
		});

		$(document).on('click', function (e) {
			if ($(e.target).closest('.js-toggle, .js-toggle-btn').length == 0) {
				$('.js-toggle, .js-toggle-btn').removeClass('is-open is-active');
			}
		});
	}

	// --------------------------------------------------------------------------
	// Slick
	// --------------------------------------------------------------------------


  function slick($selector) {  

  	//$selector == undefined - it is for first initialising slick on page. || $selector == '.some-selector' - for a initialising slick on ajax loaded content

    var slickPrev = '<button class="slick-arrow slick-prev"><svg class="icon-prev"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-prev"></use></svg></button>',
        slickNext = '<button class="slick-arrow slick-next"><svg class="icon-next"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-next"></use></svg></button>';

    var slickPrevThin = '<button class="slick-arrow slick-prev"><svg class="icon-thin-left"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-thin-left"></use></svg></button>',
        slickNextThin = '<button class="slick-arrow slick-next"><svg class="icon-thin-right"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-thin-right"></use></svg></button>';

    var slickPrevSmall = '<button class="slick-arrow slick-prev"><svg class="icon-arrow-left"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-arrow-left"></use></svg></button>',
        slickNextSmall = '<button class="slick-arrow slick-next"><svg class="icon-arrow-right"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-arrow-right"></use></svg></button>';

    var slickPrevGallery = '<button class="slick-arrow slick-prev"><svg class="icon-arrow-up"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-arrow-up"></use></svg></button>',
        slickNextGallery = '<button class="slick-arrow slick-next"><svg class="icon-arrow-down"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-arrow-down"></use></svg></button>';

    // -----

    if($selector == undefined || $selector == '.js-gallery'){     

      $('.js-gallery').each(function () {

        var gallery = $(this),
            gallerySlides = gallery.find('.js-gallery-slides').not('.slick-initialized'),
            galleryThumbs = gallery.find('.js-gallery-thumbs');

		if (gallerySlides.length == 0) { return; } //Skip this frame if slick has already been initialized

        gallerySlides.slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          arrows: false,
          fade: false,
          infinite: false,
          asNavFor: galleryThumbs
        });

        galleryThumbs.slick({
          vertical: false,
          slidesToShow: 4,
          slidesToScroll: 1,
          asNavFor: gallerySlides,
          arrows: true,
          dots: false,
          focusOnSelect: true,
          mobileFirst: true,
          nextArrow: slickNextGallery,
          prevArrow: slickPrevGallery,
          variableWidth: true,
          infinite: false,
          responsive: [{
            breakpoint: 991,
            settings: {
              vertical: true,
              variableWidth: false
            }
          }]
        }); 
      });
    }
    // -----

    if($selector == undefined || $selector == '.js-slick-other'){
      var activElem = 0;
//      if ($('#sku input[name=\'activelempopup\']').val()){
      if ($('#popupprod input[name=\'activelempopup\']').val()){
      	activElem = parseInt($('#popupprod input[name=\'activelempopup\']').val()); 
      }           	
      $('.js-slick-other').slick({
      	centerMode: true,
        slidesToShow: 5,
        slidesToScroll: 1,
        initialSlide: activElem,
        arrows: true,
        fade: false,
		mobileFirst: true,
		nextArrow: slickNextSmall,
	    prevArrow: slickPrevSmall,
	    variableWidth: true
	  });
    }
    // -----

    if($selector == undefined || $selector == '.js-slick-nav'){ 
	 if (screen.width > 991) {
      $('.js-slick-nav').slick({
        dots: false,
        arrows: true,
        cssEase: 'cubic-bezier(0.645, 0.045, 0.355, 1)',
        mobileFirst: true,
        nextArrow: slickNextThin,
        prevArrow: slickPrevThin
      });
     } 
    } 
    // ------
    if($selector == undefined || $selector == '.js-slick-slides'){
	$('.js-slick-slides').each(function(){
	  $(this).slick({
        dots: false,
        arrows: true,
        cssEase: 'cubic-bezier(0.645, 0.045, 0.355, 1)',
        mobileFirst: true,
        nextArrow: slickNext,
        prevArrow: slickPrev
      });
    });
    }
    // ------

    if($selector == undefined || $selector == '.js-slick-recomended'){
      $('.js-slick-recomended').slick({
        slidesToShow: 2,
        slidesToScroll: 1,
        infinite: false,
        arrows: true,
        variableWidth: true,
        mobileFirst: true,
        nextArrow: slickNextSmall,
        prevArrow: slickPrevSmall,
        responsive: [{
          breakpoint: 320,
          settings: {
            slidesToShow: 1
          }
        },{
 //         breakpoint: 400,
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 768,
          settings: {
            slidesToShow: 3
          }
        }, {
          breakpoint: 1200,
          settings: {
            slidesToShow: 4,
			lazyLoad: 'progressive'
          }
        }]

      });
    }

    if($selector == undefined || $selector == '.js-slick-set-x2'){
      $('.js-slick-set-x2').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: true,
        variableWidth: true,
        mobileFirst: true,
        nextArrow: slickNextSmall,
        prevArrow: slickPrevSmall,
        responsive: [{
          breakpoint: 768,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 992,
          settings: {
            slidesToShow: 2,
            variableWidth: false,
			arrows: false,
          }
        }]

      });
    }
	
    if($selector == undefined || $selector == '.js-slick-set-x3'){
      $('.js-slick-set-x3').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: true,
        variableWidth: true,
        mobileFirst: true,
        nextArrow: slickNextSmall,
        prevArrow: slickPrevSmall,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 768,
          settings: {
            slidesToShow: 3
          }
        }, {
          breakpoint: 992,
          settings: {
            slidesToShow: 3,
            variableWidth: true,
			arrows: false,
          }
        }]

      });
    }
	
	if($selector == undefined || $selector == '.js-slick-set'){
		$('.js-slick-set').slick({
			slidesToShow: 1,
			slidesToScroll: 1,
			infinite: false,
			arrows: true,
			nextArrow: slickNextSmall,
			prevArrow: slickPrevSmall,
			variableWidth: true,
			mobileFirst: true,
			responsive: [{
			    breakpoint: 480,
			    settings: {
				slidesToShow: 2,
				variableWidth: true
			  }
			}, {
				breakpoint: 768,
				settings: {
					slidesToShow: 3,
					variableWidth: true
				}
			}, {
				breakpoint: 1200,
				settings: {
					slidesToShow: 4,
					variableWidth: true,
					lazyLoad: 'progressive'
				}
			}]

		});
    }
	
    if($selector == undefined || $selector == '.js-slick-products'){
      $('.js-slick-products').not('.slick-initialized').slick({
        dots: false,
        arrows: true,
        cssEase: 'cubic-bezier(0.645, 0.045, 0.355, 1)',
        mobileFirst: true,
        nextArrow: slickNextSmall,
        prevArrow: slickPrevSmall
        // draggable: false
      });
    }

    if($selector == undefined || $selector == '.js-slick-categories'){
    $(window).on('load resize orientationchange', function() {
        $('.js-slick-categories').each(function(){
            var $s = $(this);
            if ($(window).width() > 768) {
                if ($s.hasClass('slick-initialized')) {
                    $s.slick('unslick');
                }
            } else {
                if (!$s.hasClass('slick-initialized')) {
                    $s.slick({
						slidesToShow: 1,
						slidesToScroll: 1,
						infinite: false,
						arrows: false,
						mobileFirst: true,
						variableWidth: true,
						responsive: [{
						  breakpoint: 576,
						  settings: {
							slidesToShow: 2
						  }
						}, {
						  breakpoint: 768,
						  settings: {
							slidesToShow: 3
						  }
						}]
                    });
                }
            }
        });
    });

    }

    // -----
    if($selector == undefined || $selector == '.js-slick-media'){
      $('.js-slick-media').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: false,
        variableWidth: true,
        mobileFirst: true,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2,
          }
        }, {
          breakpoint: 768,
          settings: {
            slidesToShow: 3,
          }
        }, {
          breakpoint: 1200,
          settings: {
            slidesToShow: 4,
			lazyLoad: 'progressive'
          }
        }]

      });
    }

    // -----

    if($selector == undefined || $selector == '.js-slick-category'){
      $('.js-slick-category').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: false,
        mobileFirst: true,
        variableWidth: true,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 768,
          settings: {
            slidesToShow: 3
          }
        }, {
          breakpoint: 992,
          settings: {
            slidesToShow: 4
          }
        }]
      });
    }

    // -----
    if($selector == undefined || $selector == '.js-slick-offices'){
      $('.js-slick-offices').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: false,
        variableWidth: true,
        mobileFirst: true,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 1199,
          settings: {
            slidesToShow: 3,
            variableWidth: false
          }
        }]

      });
    }
    // -----
    if($selector == undefined || $selector == '.js-slick-article'){
      $('.js-slick-article').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        infinite: false,
        arrows: false,
        variableWidth: true,
        mobileFirst: true,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 991,
          settings: {
            slidesToShow: 3
          }
        }, {
          breakpoint: 1199,
          settings: {
            slidesToShow: 4,
			lazyLoad: 'progressive'
          }
        }]

      });
    }
	// -----
    if($selector == undefined || $selector == '.js-slider-brands'){
      $('.js-slider-brands').slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        infinite: false,
        arrows: false,
		dots: false,
        mobileFirst: true,
        responsive: [{
          breakpoint: 480,
          settings: {
            slidesToShow: 2
          }
        }, {
          breakpoint: 991,
          settings: {
            slidesToShow: 3
          }
        }, {
          breakpoint: 1199,
          settings: {
            slidesToShow: 5
          }
        }]

      });
    }
	
		// ------
    if($selector == undefined || $selector == '.js-slick-reviews_homepage'){
		$('.js-slick-reviews_homepage').slick({
			slidesToShow: 1,
			slidesToScroll: 1,
			infinite: false,
			arrows: true,
			variableWidth: true,
			mobileFirst: true,
			nextArrow: slickNextSmall,
			prevArrow: slickPrevSmall,
			responsive: [{
				breakpoint: 480,
				settings: {
					slidesToShow: 2
				}
			}, {
				breakpoint: 768,
				settings: {
					slidesToShow: 2,
					variableWidth: true
				}
			}, {
				breakpoint: 1200,
				settings: {
					slidesToShow: 3,
					variableWidth: false,
					lazyLoad: 'progressive'
				}
			}]

		});
    }
	
    if($selector == undefined || $selector == '.js-slick-reviews_catalog'){
		$('.js-slick-reviews_catalog').slick({
			slidesToShow: 1,
			slidesToScroll: 1,
			infinite: false,
			arrows: true,
			nextArrow: slickNextSmall,
			prevArrow: slickPrevSmall

		});
    }
	
		$('.js-slick-compare').on('init afterChange', function (event, slick) {

			$.fn.matchHeight._update();

			$('.js-slick-compare-prev, .js-slick-compare-next').removeClass('slick-disabled');

			if ($('.js-slick-compare').find('.slick-prev').is('.slick-disabled')) {
				$('.js-slick-compare-prev').addClass('slick-disabled');
			}
			if ($('.js-slick-compare').find('.slick-next').is('.slick-disabled')) {
				$('.js-slick-compare-next').addClass('slick-disabled');
			}
		});

		$(window).on('resize', function (event) {
			$.fn.matchHeight._update();
		});

    if($selector == undefined || $selector == '.js-slick-compare'){
		$('.js-slick-compare').slick({
			slidesToShow: 2,
			slidesToScroll: 1,
			infinite: false,
			arrows: true,
			mobileFirst: true,
			responsive: [{
				breakpoint: 768,
				settings: {
					slidesToShow: 3
				}
			}]
		});
    }
	
		$(document).on('click', '.js-slick-compare-prev', function (event) {
			event.preventDefault();
			$('.js-slick-compare').slick('slickPrev');
		});

		$(document).on('click', '.js-slick-compare-next', function (event) {
			event.preventDefault();
			$('.js-slick-compare').slick('slickNext');
		});
  }

	// --------------------------------------------------------------------------
	// Readmore
	// --------------------------------------------------------------------------

	function readmore() {
		$('.js-readmore').each(function(){
			$(this).readmore({
				speed: 75,
				collapsedHeight: 154,
				heightMargin: 0,
				moreLink: '<a href="#" class="seo__readmore-link"><span>' + $(this).attr('data-more') + '</span></a>',
				lessLink: '<a href="#" class="seo__readmore-link"><span>' + $(this).attr('data-less') + '</span></a>',
				embedCSS: false,
				blockCSS: 'display: block; width: 100%;',
				startOpen: false,
				beforeToggle: function() { 
					$('.js-readmore').removeClass('js-readmore-open'); 
				  },
				afterToggle: function(trigger, element, expanded) {
					if(expanded) {
					 $('.js-readmore').addClass('js-readmore-open');
					}
				  },
				blockProcessed: function() {}
			});
		});
	}

	// --------------------------------------------------------------------------
	// Phone Mask
	// --------------------------------------------------------------------------


	function phone() {

		$('input[name=tel]').inputmask({ "mask": "+7 (999) 999-99-99", showMaskOnFocus: true });
	}

	// --------------------------------------------------------------------------
	// Date Mask
	// --------------------------------------------------------------------------


	function date() {

		$('input[name=date]').inputmask({ "mask": "99.99.9999", showMaskOnFocus: true, alias: "дд.мм.гггг" });
	}

	// --------------------------------------------------------------------------
	// Countdown
	// --------------------------------------------------------------------------

	function countdown() {
		$('.js-countdown-time').each(function(){
			var text = $(this).attr('data-text-countdown').split(',');
			
			$(this).countDown({
				css_class: 'countdown',
				always_show_days: true,
				with_separators: false,
				label_dd: text[0],
				label_hh: text[1],
				label_mm: text[2],
				label_ss: text[3]
			});
		});
	}

	// --------------------------------------------------------------------------
	// FormStyler for .app-select, .app-number
	// --------------------------------------------------------------------------

	function formstyler() {

		$('.ui-select, .ui-number').styler({
			selectSearch: false,
			selectSmartPositioning: false,
			onSelectOpened: function onSelectOpened() {
				// $(this).find('ul').mCustomScrollbar();
			}
		});

		$('.ui-select').on('change', function (event) {
			$(this).trigger('refresh');
		});
	}

	// --------------------------------------------------------------------------
	// Fancybox
	// --------------------------------------------------------------------------

	function fancybox() {

		$('[data-fancybox]').fancybox({
			infobar: false,
			toolbar: false,
			touch: false,
			transitionEffect: 'slide',
			afterLoad: function afterLoad() {
				$('.js-gallery').find('.js-gallery-slides, .js-gallery-thumbs').slick('refresh');
			}

		});

		$('[data-fancybox="images"]').fancybox({
			infobar: true,
			toolbar: true,
			touch: true,
			transitionEffect: 'slide',
			youtube : {
				controls : 0,
				showinfo : 0,
				autoplay : 1
			}
		});
	}

	// --------------------------------------------------------------------------
	// TabsData
	// --------------------------------------------------------------------------

	function tabsData() {

		$('[data-tabs]').on('click', '[data-tabs-btn]', function (event) {
			event.preventDefault();

			var tab = $(this),
			    tab_id = $(this).attr('data-tabs-btn');

			$(this).closest('[data-tabs]').find('[data-tabs-btn]').removeClass('is-active');
			$(this).closest('[data-tabs]').find('[data-tabs-content]').removeClass('is-active');

			$(this).closest('[data-tabs]').find('[data-tabs-btn=' + tab_id + ']').addClass('is-active');
			$(this).closest('[data-tabs]').find('[data-tabs-content=' + tab_id + ']').addClass('is-active');

			if (matchMedia('only screen and (max-width: 480px)').matches) {
				$('.details__tabs').each(function () {
					var additionalOffset = 0;
					if ($('.nav').is('.js-sticky')) {
						additionalOffset = $('.nav').innerHeight();
					} else {
						additionalOffset = $('.js-sticky').innerHeight();
					}

					$('html, body').animate({ scrollTop: tab.offset().top - additionalOffset }, 250);
				});
			}
		});
	}

	// --------------------------------------------------------------------------
	// Tabs
	// --------------------------------------------------------------------------

	function tabs() {

		$(document).on('click', '.js-tabs-btn', function (event) {
			event.preventDefault();

			var index = $(this).index();

			if ($(this).is('.is-active')) {
				$(this).removeClass('is-active').closest('.js-tabs').find('.js-tabs-content').removeClass('is-active');
			} else {
				$('.js-tabs-btn, .js-tabs-content').removeClass('is-active');
				$(this).addClass('is-active').closest('.js-tabs').find('.js-tabs-content').eq(index).addClass('is-active');
			}
		});
		// $('.js-tabs').each(function() {

		// 	$(this).find('.js-tabs-btn').each(function(i) {
		// 		$(this).click(function(){
		// 			$(this).addClass('is-active').siblings().removeClass('is-active')
		// 			.closest('.js-tabs').find('.js-tabs-content').removeClass('is-active').eq(i).addClass('is-active');
		// 		});
		// 	});

		// });
	}

	function zoom() {
		if (window.innerWidth > 767) {
			$('[data-zoom-inner]').each(function () {
				$(this).removeAttr('title');
				$(this).ezPlus({
					zoomType: 'inner',
					cursor: 'pointer',
					borderSize: 0,
					borderColour: '#3B55E6',
					responsive: true,
					easing: false,
					zoomWindowFadeIn: 400,
					zoomWindowFadeOut: 0,
					containLensZoom: false,
					gallery: false,
					zoomContainerAppendTo: '.app',
					imageCrossfade: true
				});
			});
			$('[data-zoom-lens]').each(function () {
				$(this).removeAttr('title');
				$(this).ezPlus({
					zoomType: 'lens',
					cursor: 'pointer',
					borderSize: 0,
					borderColour: '#3B55E6',
					responsive: true,
					easing: false,
					zoomWindowFadeIn: 400,
					zoomWindowFadeOut: 0,
					containLensZoom: true,
					gallery: false,
					imageCrossfade: true,
					zoomContainerAppendTo: '.app',
					lensShape: 'square'
				});
			});
			$('[data-zoom-window]').each(function () {
				$(this).removeAttr('title');
				$(this).ezPlus({
					zoomType: 'window',
					cursor: 'pointer',
					borderSize: 0,
					borderColour: '#3B55E6',
					responsive: false,
					easing: false,
					zoomWindowFadeIn: 400,
					zoomWindowFadeOut: 0,
					containLensZoom: false,
					gallery: false,
					zoomContainerAppendTo: '.app',
					imageCrossfade: true
				});
			});
		} else {
			$('.sku__slides-item--ezplus').each(function () {
				$(this).attr('data-fancybox', 'images');
				$(this).attr('href',$(this).data('popup'));
				fancybox();
			});

		}
	}

	// --------------------------------------------------------------------------
	// Scrollbar
	// --------------------------------------------------------------------------


	function scrollbar() {}

	// $('.js-scroll-y').mCustomScrollbar({
	// 	axis:"y",
	// 	scrollbarPosition: 'inside',
	// 	autoHideScrollbar: true,
	// 	scrollInertia: 100,
	// 	autoDraggerLength: true,
	// 	mouseWheel:{
	// 		preventDefault: true
	// 	},
	// 	advanced: {
	// 		updateOnContentResize: true
	// 	}

	// });


	// function scrollbarSet() {

	// 	if (matchMedia('only screen and (max-width: 1199px)').matches) {

	// 		$('.js-scroll-x').mCustomScrollbar({
	// 			axis:"x",
	// 			scrollbarPosition: 'outside',
	// 			autoHideScrollbar: true,
	// 			scrollInertia: 100,
	// 			autoDraggerLength: true,
	// 			mouseWheel:{
	// 				preventDefault: false
	// 			},
	// 			advanced: {
	// 				updateOnContentResize: true
	// 			}

	// 		});

	// 	}
	// 	else {
	// 		$('.js-scroll-x').mCustomScrollbar('destroy');
	// 	}
	// }

	// scrollbarSet();

	// $(window).on('resize', scrollbarSet);


	// --------------------------------------------------------------------------
	// Autosize
	// --------------------------------------------------------------------------


	function autosizeTextarea() {
		autosize($('textarea'));
	}

	// --------------------------------------------------------------------------
	// Range
	// --------------------------------------------------------------------------


	function rangeSlider() {

		function abc(n) {
			return (n + "").split("").reverse().join("").replace(/(\d{3})/g, "$1 ").split("").reverse().join("").replace(/^ /, "");
		}
		var min_price_current = $('#min_price_current').val(),
			max_price_current = $('#max_price_current').val();

		$('.ui-range__slider').ionRangeSlider({
			type: "double",
			from: min_price_current,
			to: max_price_current,
			step: 1,
			min: 0,
			max: 1000000,
			hide_min_max: true,
			hide_from_to: true,
			force_edges: true,
			grid: false,
			onFinish: function (data) {// Called then action is done and mouse is released
            	sliderProducts($('.ui-range__slider'));
        	}
		});

		$('.ui-range__slider').on('change', function (event) {
			event.preventDefault();

			var range = $(this),
			    rangeData = range.data("ionRangeSlider"),
			    rangeDataFrom = abc(range.data("from")),
			    rangeDataTo = abc(range.data("to")),
			    rangeDataWallet = range.data('wallet'),
			    inputFrom = range.closest('.ui-range').find('.ui-range__from'),
			    inputFromPrefix = inputFrom.data('prefix'),
			    inputTo = range.closest('.ui-range').find('.ui-range__to'),
			    inputToPrefix = inputTo.data('prefix');

			inputFrom.val(inputFromPrefix + ' ' + rangeDataFrom + ' ' + rangeDataWallet);
			inputTo.val(inputToPrefix + ' ' + rangeDataTo + ' ' + rangeDataWallet);
			$("#min_price_current").val(range.data("from"));
			$("#max_price_current").val(range.data("to"));
		});


		$('input[name^=\'filter\']').on('click', function(e) { 
			sliderProducts($(this));
		});








	}

	// --------------------------------------------------------------------------
	// Validate
	// --------------------------------------------------------------------------


	/* function validate() {

		$.validator.addMethod("tel", function (value, element) {
			return this.optional(element) || /^\+\d \(\d{3}\) \d{3}-\d{2}-\d{2}$/.test(value);
		});

		$.validator.addMethod("date", function (value, element) {
			return this.optional(element) || /^\d{2}.\d{2}.\d{4}$/.test(value);
		});

		var validateHighlight = function validateHighlight(element) {
			$(element)
			// .addClass("is-error").removeClass("is-success")
			.parent().addClass("is-error").removeClass('is-success').append('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg>').find('.icon-success').remove();
		};
		var validateUnhighlight = function validateUnhighlight(element) {
			$(element)
			// .addClass('is-success').removeClass("is-error")
			.parent().addClass('is-success').removeClass("is-error").append('<svg class="icon-success"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-success"></use></svg>').find('.icon-error').remove();
		};

		var validateErrorPlacement = function validateErrorPlacement(error, element) {
			error.addClass('ui-error');
			error.appendTo(element.parent());

			// return true;
		};

		$('.js-validate').each(function (index, value) {

			$(this).validate({
				// errorClass: 'is-error',
				// validClass: 'is-success is-changed',
				errorElement: "span",
				ignore: ":disabled,:hidden",
				highlight: validateHighlight,
				unhighlight: validateUnhighlight,
				rules: {
					firstLastName: "required",
					firstName: "required",
					secondName: "required",
					tel: {
						required: true,
						tel: true
					},
					email: {
						required: true,
						email: true
					},
					date: {
						required: true,
						date: true
					},
					city: {
						required: true
					},
					address: {
						required: true
					},
					message: {
						required: true
					},
					passwordCurrent: {
						required: true
					},
					password: {
						required: true,
						minlength: 6
					},
					passwordConfirm: {
						required: true,
						equalTo: "#password"
					},
					check: {
						required: true
					},
					select: {
						required: true
					}
					// radioPayer: {
					// 	required: true
					// },
					// radioDelivery: {
					// 	required: true
					// },
					// radioPayment: {
					// 	required: true
					// }

				},
				messages: {
					firstLastName: 'Введите имя и фамилию',
					firstName: 'Введите имя',
					secondName: 'Введите фамилию',
					tel: 'Введите номер телефона',
					email: 'Введите email',
					date: 'Введите дату рождения',
					city: 'Введите город',
					address: 'Введите адресс',
					message: 'Введите ваше сообщение',
					passwordCurrent: 'Введите текущий пароль',
					password: 'Введите пароль',
					passwordConfirm: {
						required: 'Подтвердите пароль',
						equalTo: 'Пароли не совпадают'
					},
					select: 'Выберите пункт',
					check: false
					// radioPayer: false,
					// radioDelivery: false,
					// radioPayment: false


				},
				errorPlacement: validateErrorPlacement,
				submitHandler: function submitHandler(form) {}
			});
		});
	} */

	// --------------------------------------------------------------------------
	// catalogOptions
	// --------------------------------------------------------------------------


	function catalogOptions() {

		// $(document).on('click', '.js-open', function(event) {
		// 	event.preventDefault();

		// 	var dataClass = $(this).data('open');
		// 	$(dataClass).addClass('is-open');
		// });


		// $(document).on('click', '.js-close', function(event) {
		// 	event.preventDefault();

		// 	var dataClass = $(this).data('close');
		// 	$(dataClass).removeClass('is-open');
		// });


		$('.js-filter-open').on('click', function (event) {
			event.preventDefault();
			$('html').addClass('is-filter-open');
		});

		$('.js-filter-close').on('click', function (event) {
			event.preventDefault();
			$('html').removeClass('is-filter-open');
		});

		$(document).on('click', '.js-options-btn', function (event) {
			event.preventDefault();

			if ($(this).closest('.js-options').is('.is-open')) {
				$(this).closest('.js-options').removeClass('is-open');
			} else {
				$('.js-options').removeClass('is-open');
				$(this).closest('.js-options').addClass('is-open');
			}
		});

		$(document).on('click', '.js-options-item', function (event) {
			event.preventDefault();

			$(this).addClass('is-active').siblings().removeClass('is-active').closest('.js-options').removeClass('is-open').find('.js-options-btn').html($(this).html());

			var sub = $(this);
			if ($(this).attr('data-href') != undefined) {
				location = $(this).attr('data-href');
			}else if ($(this).attr('data-option') != undefined) {
				setCatView(sub);
			}
		});

		$(document).on('click', function (e) {
			if ($(e.target).closest('.js-options').length == 0) {
				$('.js-options').removeClass('is-open');
			}
		});
	}

	// --------------------------------------------------------------------------
	// Lazyload
	// --------------------------------------------------------------------------

	function lazyLoad() {

		$('.js-lazy').lazy({
			effect: "fadeIn",
			effectTime: 200,
			threshold: 0
		});
		
		$('[data-src]').lazy({
			effect: "fadeIn",
			effectTime: 200,
			threshold: 100,
			visibleOnly: false
		});
	}

	// --------------------------------------------------------------------------
	// Fixed
	// --------------------------------------------------------------------------

	function stickyKit() {

		if ($('.js-sticky').length && $('.app').length) {
			$('.js-sticky').stick_in_parent({ offset_top: 0, parent: '.app', sticky_class: 'is-sticky' });
			var h = $('.js-sticky').outerHeight();
		} else {
			var h = 0;
		}
		
		$('.js-fixed').stick_in_parent({ offset_top: 20 + h });
		
		/*
		if (screen.width <= 991) {
			$('.js-fixed-cart').trigger('sticky_kit:detach');
		} else {
			$('.js-fixed-cart').stick_in_parent({ offset_top: 20 + h });
		}

		$(window).on('resize', function (event) {
			if (screen.width <= 991) {
				$('.js-fixed-cart').trigger('sticky_kit:detach');
			} else {
				$('.js-fixed-cart').stick_in_parent({ offset_top: 20 + h });
			}
		});
		*/
		
	}

	// --------------------------------------------------------------------------
	// Preload
	// --------------------------------------------------------------------------


	function preload() {
		setTimeout(function () {
			$('html').addClass('is-loaded');
		}, 200);
	}


	// --------------------------------------------------------------------------
	// slickPlay
	// --------------------------------------------------------------------------


	function slickPlay() {
		if (screen.width > 991) {
			$('.js-slick-products').mouseover(function() {
				$(this).slick('slickSetOption', {
				   speed: 500,
				   autoplaySpeed: 500,
				}, false).slick('slickPlay');
			});
			$('.js-slick-products').mouseout(function() {
			   $(this).slick('slickPause');
			});
		}
	}

	// --------------------------------------------------------------------------
	// Nav Sticky 
	// --------------------------------------------------------------------------

	$(document).on('click', '.js-open-nav', function (event) {
		event.preventDefault();
		$('.js-trigger-nav').trigger('click');
	});

	$(document).on('click', '.js-open-search', function (event) {
		event.preventDefault();
		$('.js-trigger-search').trigger('click');
	});

	$(document).on('click', '.js-open-cart', function (event) {
		event.preventDefault();
		$('.js-trigger-cart').trigger('click');
	});


/* Agree to Terms */
$(document).delegate('.agree', 'click', function(e) {
	e.preventDefault();

	$('#modal-agree').remove();

	var element = this;
	
// BS4 SAMPLE 
/*
	$.ajax({
		url: $(element).attr('href'),
		type: 'get',
		dataType: 'html',
		success: function(data) {
			html  = '<div id="modal-agree" class="modal fade">';
			html += '  <div class="modal-dialog">';
			html += '    <div class="modal-content">';
			html += '      <div class="modal-header">';
			html += '        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><svg class="icon-close"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-close"></use></svg></button>';
			html += '        <h4 class="modal-title">' + $(element).text() + '</h4>';
			html += '      </div>';
			html += '      <div class="modal-body">' + data + '</div>';
			html += '    </div>';
			html += '  </div>';
			html += '</div>';

			$('body').append(html);

			$('#modal-agree').modal('show');
		}
	});
*/
// BS4 SAMPLE END

		$.ajax({
			url: $(element).attr('href'),
			type: 'get',
			dataType: 'html',
			success: function(data) {
				html  = '<div class="popup popup--570" id="modal-agree">';
				html += '  <div class="writeus">';
				html += '        <span class="writeus__heading">' + $(element).text() + '</span>';
				html += '      <div class="writeus__form">' + data + '</div>';
				html += '  </div>';
				html += '</div>';

				$('body').append(html);

				$.fancybox.open([{
					src  : '#modal-agree',
					opts : {
						slideClass : 'popup--agree', 
						btnTpl : {
							smallBtn   : '<button data-fancybox-close class="popup__close" title="{{CLOSE}}"><svg class="icon-close"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-close"></use></svg></button>'
						},
						autoFocus : false,
						touch: false
					}
				}]);
			}
		});
});

// Show/hide additional buttons in cart depend on empty cart or not
function cartExrtaElem(total) {
	if (total) {
		$('#cart  .cart__clear').show();
		$('#cart  .cart__foot').show();
	}else{
		$('#cart  .cart__clear').hide();
		$('#cart  .cart__foot').hide();
	}
}

function getCompareWish() {
	$('#compare').load('index.php?route=common/header/getcompare');			
	$('#wish').load('index.php?route=common/header/getwish');
	$.ajax({
		url: 'index.php?route=common/header/getwishcompare',
		type: 'post',
		dataType: 'json',
		data: '',
		success: function (data) {
			$('#wishcomptotall').html(data['counTotall']);
			if (data['counTotall'] == 0) {
				$('#wishcomptotall').hide();
			}else{
				$('#wishcomptotall').show();
			}
		}
	}); 
}

function sendYM(event){
	if (typeof (ym) === "function") { // If YandexMetric is turned on in admin's pannel
		if($("meta[property='yandex_metric']").attr('content')){
			var yandex_metric = $("meta[property='yandex_metric']").attr('content');
			ym(yandex_metric, 'reachGoal', event);
		}
	}
}

function sendMetrics(eventname){
	if (typeof (gtag) === "function") { // If Google Metric is turned on in admin's pannel 
		gtag('event', eventname, {});
	}	
	if (typeof (ym) === "function") { // If YandexMetric is turned on in admin's pannel 
		if($("meta[property='yandex_metric']").attr('content')){
			var yandex_metric = $("meta[property='yandex_metric']").attr('content');
			ym(yandex_metric, 'reachGoal', eventname);
		}
	}		
}

function sendGA(datapr,event){

		if (typeof (gtag) !== "function") { // If GA is turned off in admin's pannel, return 
			return;
		}

		$.ajax({
	  			url: 'index.php?route=product/product/analystdata',
				dataType: "json",
				type: "POST",
				data: datapr,
				success: function(item){
					if(!!item){
						gtag('event', event, item);
					}
				}
			});
}

function sendGAch(datapr,event){ 
		if (typeof (gtag) !== "function") { // If GA is turned off in admin's pannel, return 
			return;
		}
		$.ajax({
	  			url: 'index.php?route=product/product/analystdataorder',
				dataType: "json",
				type: "POST",
				data: datapr,
				success: function(data){ 
//					if(!!data){
						setTimeout(function(){

							gtag('event', event, data);

						},100);
//					}
				}
		});
}

function add2cartlist(){ 
  $(document).on('click', '.products__line-action button,.products__micro-action button', function(e) {	
    var product_id = $(this).attr('data-for');
    var qty = $(this).prev(".jq-number").find('input.ui-number').val();
    cart.add(product_id, qty);
  });
}

	
// Cart add remove functions
var cart = {
	'add': function(product_id, quantity) {
		var datapr = 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1);
		$.ajax({
			url: 'index.php?route=checkout/cart/add',
			type: 'post',
			data: datapr,
			dataType: 'json',
			beforeSend: function() {
			},
			complete: function() {
			},
			success: function(json) {
				$('.alert-dismissible, .text-danger').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {

					// Need to set timeout otherwise it wont update the total
					setTimeout(function () {
						$('#cart-total').html('<mark class="cart__counter" id="cart-total"> ' + json['total'] + '</mark>');
					}, 100);

					cartExrtaElem(json['total']);
					
					if ($('.js-cart-call').length) {
						$('.js-cart-call>button').trigger('click');
					} else {
						$('.app').append($('<div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert"> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
					}
					
					sendYM('technics_addtocart_catalog');
					sendGA(datapr,'technics_addtocart_catalog');
						
					$('#cart .cart__body').load('index.php?route=common/cart/info div.cart__scroll',function(){
						$('#cart input[type=\'number\']').styler();
					});
					
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'popupadd': function(product_id) { // Add products to cart from PopUP Window

			var datas = $('#popupprodid_'+product_id).find('form').serialize();
			$.ajax({
				url: 'index.php?route=checkout/cart/add',
				type: 'post',
				data: datas,
				dataType: 'json',
				beforeSend: function() {
					$('.js-btn-add-cart').attr('disabled', 'disabled');
				},
				complete: function() {
					$('.js-btn-add-cart').removeAttr('disabled');
				},
				success: function(json) {
					$('.alert, .ui-error').remove();
					$('[id^="input-option"],.ui-field').removeClass('is-error');

					if (json['error']) {
						if (json['error']['option']) {
							for (i in json['error']['option']) {
								var element = $('#input-option' + i.replace('_', '-'));
								
								if (element.parent().hasClass('ui-select')) {
									element.parent().after('<span class="error ui-error">' + json['error']['option'][i] + '</span>').parent().addClass('is-error');
								} else if (element.hasClass('ui-input') || element.hasClass('ui-textarea')) {
									element.after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['option'][i] + '</span>').parent().addClass('is-error');
								} else {
									element.after('<span class="error ui-error">' + json['error']['option'][i] + '</span>').addClass('is-error');
								}
							}
						}

						if (json['error']['recurring']) {
							$('select[name=\'recurring_id\']').parent().after('<span class="error ui-error">' + json['error']['recurring'] + '</span>').parent().addClass('is-error');
						}
						
					}
					if (json['success']) {						
						$.fancybox.close();
						cartExrtaElem(json['total']);
						
						if ($('.js-cart-call').length) {
							$('.js-cart-call>button').trigger('click');
						} else {
							$('.app').append($('<div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert"> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
						}
						$('#cart-total').html('<mark class="cart__counter" id="cart-total"> ' + json['total'] + '</mark>');

						//sendYM('technics_addtocart_product');
						//sendGA(datapr,'technics_addtocart_product');
						
						$('#cart .cart__body').load('index.php?route=common/cart/info div.cart__scroll',function(){
							$('#cart input[type=\'number\']').styler();
						});	
					}
					
				},
				error: function(xhr, ajaxOptions, thrownError) {
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});
	},
	'add2cartFast': function($data) {

	      $.ajax({
	        url: 'index.php?route=extension/module/technics/technicscart/fastadd2cart',
	        type: 'post',
	        data: $data,
	        dataType: 'json',
	        beforeSend: function() {
	          $('.quickbuy-send').attr('disabled', 'disabled');
	          //$('.alert, .product-page__input-box-error, .popup-simple__inner-error-text').remove();
	          $('.alert, .ui-error, .icon-error').remove();
	          $('.ui-group, .ui-field').removeClass('is-error');
	        },
	        complete: function() {
	          $('.quickbuy-send').removeAttr('disabled');
	        },
	        success: function(json) {
			$('.alert, .ui-error').remove();
			$('[id^="input-option"],.ui-field').removeClass('is-error');

	          if (json['error']) { 
	            if (json['redirect']) {
	                      
	              setTimeout(function() { $.fancybox.close() }, 2000);
	              setTimeout(function() { location = json['redirect']; }, 3000);

	            }
	            if (json['error']['option']) {
	              for (i in json['error']['option']) {
	                var element = $('#input-option' + i.replace('_', '-'));
	                
						if (element.parent().hasClass('ui-select')) {
							element.parent().after('<span class="error ui-error">' + json['error']['option'][i] + '</span>').parent().addClass('is-error');
						} else if (element.hasClass('ui-input') || element.hasClass('ui-textarea')) {
							element.after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['option'][i] + '</span>').parent().addClass('is-error');
						} else {
							element.after('<span class="error ui-error">' + json['error']['option'][i] + '</span>').addClass('is-error');
						}
	              }

	              $('.writeus__heading').before('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + 'Заполните обязательные поля' + ' </div>');
	                setTimeout(function(){
	                  $('.alert-danger').remove();
	                }, 2000);                     
	              setTimeout(function() { $.fancybox.close() }, 1500);
	  
	            }

	            if (json['error']['error_stock']) {

	              $('.writeus__heading').before('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['error']['error_stock'] + ' </div>');
	                setTimeout(function(){
	                  $('.alert-success').remove();
	                }, 2000);                     
	              setTimeout(function() { $.fancybox.close() }, 1500);
	  
	            }

	            if (json['error']['error_min_warning']) {
	                $('.writeus__heading').before('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['error']['error_min_warning'] + ' </div>');                   	  
	            }

	            if (json['error']['recurring']) {
	              $('select[name=\'recurring_id\']').parent().after('<span class="error ui-error">' + json['error']['recurring'] + '</span>').parent().addClass('is-error');
	              $.fancybox.close();
	            }
	            if (json['error']['popup']) { 
	              if (json['error']['popup']['name']) {
	                $('input[name=\'name\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['popup']['name'] + '</span>').parent().addClass('is-error');
	              }
	              if (json['error']['popup']['phone']) {
	                $('input[name=\'phone\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['popup']['phone'] + '</span>').parent().addClass('is-error');
	              } 
	              if (json['error']['popup']['email']) {
	                $('input[name=\'email\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['popup']['email'] + '</span>').parent().addClass('is-error');
	              } 
	              if (json['error']['popup']['comment']) {
	                $('textarea[name=\'comment\']').after('<svg class="icon-error"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-error"></use></svg><span class="error ui-error">' + json['error']['popup']['comment'] + '</span>').parent().addClass('is-error');
	              } 
	              if (json['error']['popup']['captcha']) { 

	                $('.writeus__heading').before('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['error']['popup']['captcha'] + '</div>');
	                  setTimeout(function(){
	                    $('.alert-danger').remove();
	                  }, 2000);                     
	    
	              }
	            }
	          }
	          if (json['success']) {
	            sendMetrics('technics_buyclick_success');

	            $.fancybox.close();
				
				$('#popup-buy-click input,#popup-buy-click textarea,#popup-buy-click-cc input,#popup-buy-click-cc textarea').val('');

	            location = json['redirect'];
	          }
	          
	        },
	        error: function(xhr, ajaxOptions, thrownError) {
	          alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
	        }
	      }); 
	},
	'update': function(key, quantity) {
		$.ajax({
			url: 'index.php?route=checkout/cart/edit',
			type: 'post',
			data: key +'=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			complete: function() {
				$('#cart > button').button('reset');
			},
			success: function(json) {
				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
					$('#cart  .cart__counter').html(json['total']);
				}, 100);

				cartExrtaElem(json['total']);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout' || $('#cartcontent').length) {  
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart .cart__body').load('index.php?route=common/cart/info div.cart__scroll',function(){$('#cart input[type=\'number\']').styler()});					
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function(key) {
		$.ajax({
			url: 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
			},
			complete: function() {
			},
			success: function(json) {
				// Need to set timeout otherwise it wont update the total
					setTimeout(function () {
						if (json['total'] > 0) {
						$('#cart-total').html('<mark class="cart__counter" id="cart-total"> ' + json['total'] + '</mark>');
						} else {
						$('#cart-total').html('');
						}
					}, 100);

				cartExrtaElem(json['total']);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout' || $('#cartcontent').length) {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart .cart__body').load('index.php?route=common/cart/info .cart__scroll',function(){
						$('#cart input[type=\'number\']').styler();
					});
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'clear': function() {
		$.ajax({
			url: 'index.php?route=checkout/cart/clear',
			type: 'post',
//			data: 'key=' + 1,
			dataType: 'json',
			beforeSend: function() {
			},
			complete: function() {
			},
			success: function(json) {
				
				$('#cart-total').html('');
				
				cartExrtaElem(0);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart .cart__body').load('index.php?route=common/cart/info .cart__scroll',function(){
						$('#cart input[type=\'number\']').styler();
					});
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}
}

var voucher = {
	'add': function() {

	},
	'remove': function(key) {
		$.ajax({
			url: 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			complete: function() {
				$('#cart > button').button('reset');
			},
			success: function(json) {
				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
						if (json['total'] > 0) {
						$('#cart-total').html('<mark class="cart__counter" id="cart-total"> ' + json['total'] + '</mark>');
						} else {
						$('#cart-total').html('');
						}
				}, 100);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart .cart__body').load('index.php?route=common/cart/info div.cart__scroll',function(){$('#cart input[type=\'number\']').styler()});
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}
}

var wishlist = {
	'add': function(product_id) {
		$.ajax({
			url: 'index.php?route=account/wishlist/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			complete: function() {
				getCompareWish();
			},
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {
					$('.app').prepend($('<div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert"> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));
				}

				$('#wishlist-total span').html(json['total']);
				$('#wishlist-total').attr('title', json['total']);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function() {

	}
}

var compare = {
	'add': function(product_id) {
		$.ajax({
			url: 'index.php?route=product/compare/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			complete: function() {
				getCompareWish();
			},
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['success']) {
					$('.app').prepend($('<div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert"> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><svg class="icon-delete"><use xlink:href="catalog/view/theme/technics/sprites/sprite.svg#icon-delete"></use></svg></button></div>'));

					$('#compare-total').html(json['total']);
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function() {

	}
}

var comment = {
	'add': function(blog_id) {
		$.ajax({
			url: 'index.php?route=extension/module/technics_blog/write&blog_id=' + blog_id,
			type: 'post',
			dataType: 'json',
			data: $("#form-comment").serialize(),
			beforeSend: function() {
				$('#form-comment button').button('loading');
			},
			complete: function() {
				$('#form-comment button').button('reset');
				setTimeout(function(){
					$('.alert').remove();
				}, 5000);
			},
			success: function(json) {
				$('.alert-dismissible').remove();
				if (json['error']) {
					$('#form-comment').before('<div class="alert alert-danger fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['error'] + ' </div>');
				}
				if (json['success']) {
					$('#form-comment').before('<div class="alert alert-success fade show" role="alert" style="margin-bottom: 2.0rem;"> ' + json['success'] + ' </div>');
					$('input[name=\'name\']').val('');
					$('textarea[name=\'text\']').val('');
					$('input[name=\'email\']:checked').prop('checked', false);
				}
				if (json['redirect']) {
					document.location.reload();
				}
			}
		});
	},
}


$(document).on('ready', function() {

	// --------------------------------------------------------------------------
	// Init
	// --------------------------------------------------------------------------

	svg4everybody();
	detectTouch();
	//viewport();
	//sticky();
	nav();
	search();
	toggleBtn();
	slick();
	readmore();
	//phone();
	add2cartlist();
	countdown();
	formstyler();
	fancybox();
	tabsData();
	tabs();
	scrollbar();
//	autosizeTextarea();
	rangeSlider();
	fastCart();
	//validate();
	catalogOptions();
	lazyLoad();
	stickyKit();
	zoom();
	currlanguage();
	ocTooltip();
	LBplugin();
	fancyFastCart();
	fancyPopUp();
	checkoutStep();
	cartChange();
	callBack();
	productsView();
	doFilter();
	addSubscribe();
	technicsSet();
	chats();
	scrollToTop();
	cookieagry();
	mobiheader();
	onresize();
	slickPlay();
	preload();
});