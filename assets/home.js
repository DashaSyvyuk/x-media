import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'slick-carousel';

$('#slider').slick({
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 8000,
    dots: true,
    arrows: false,
    pauseOnHover: false
});

$("#new-products").slick({
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 4,
    autoplay: true,
    autoplaySpeed: 6000,
    dots: true,
    arrows: false,
    pauseOnHover: false,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 3,
                infinite: true,
                dots: true
            }
        },
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 2
            }
        },
        {
            breakpoint: 650,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ]
});

$("#feedbacks").slick({
    infinite: true,
    slidesToShow: 2,
    slidesToScroll: 2,
    autoplay: true,
    autoplaySpeed: 4000,
    arrows: true,
    pauseOnHover: false,
    prevArrow: '<div class="slick-prev"><img src="/images/arrow_left.png"></div>',
    nextArrow: '<div class="slick-next"><img src="/images/arrow_right.png"></div>',
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ]
});

$("#feedbacks-mobile").slick({
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    arrows: false,
    pauseOnHover: false,
});

/*window.addEventListener('scroll', function() {
    const header = document.getElementById('menu-fixed');
    const windowHeight = window.innerHeight;

    if (window.pageYOffset > windowHeight) {
        header.classList.add('top-hide');
    } else {
        header.classList.remove('top-hide');
    }
});*/
