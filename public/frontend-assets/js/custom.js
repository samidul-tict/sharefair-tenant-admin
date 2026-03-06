//   sticky
//   sticky

window.addEventListener("scroll", function () {
  const header = document.getElementById("site-header");
  // You might want to get the header's original position
  const stickyPoint = header.offsetTop;

  if (window.pageYOffset > stickyPoint) {
    header.classList.add("sticky");
  } else {
    header.classList.remove("sticky");
  }
});

//   toggle
//   toggle

const toggler = document.querySelector(".navbar-toggler");
const collapseTarget = document.querySelector("#navbarNav");

toggler.addEventListener("click", () => {
  // Toggle class “open” on the button
  toggler.classList.toggle("open");

  // Optionally, manage `aria-expanded` attribute
  const expanded = toggler.getAttribute("aria-expanded") === "true";
  toggler.setAttribute("aria-expanded", String(!expanded));

  // Also, you might want to control the collapse behaviour if not using bootstrap's JS
});

//   accordion
//   accordion

const accHeaders = document.querySelectorAll(".accordion-header");
accHeaders.forEach((header) => {
  header.addEventListener("click", () => {
    const content = header.nextElementSibling;
    const isOpen = content.classList.contains("open");

    // Close all open contents (if you want only one open at a time)
    document.querySelectorAll(".accordion-content.open").forEach((c) => {
      c.classList.remove("open");
    });
    // Also remove the active class from all headers
    accHeaders.forEach((h) => {
      h.classList.remove("active");
    });

    if (!isOpen) {
      // Open the content
      content.classList.add("open");
      // Add the active class to this header
      header.classList.add("active");
    }
  });
});

//   navbar
//   navbar

const navbarNav = document.getElementById("navbarNav");
const mq = window.matchMedia("(min-width: 992px)");

function handleWidthChange(e) {
  if (e.matches) {
    navbarNav.classList.remove("collapse");
  } else {
    // screen is wider than 991px
    navbarNav.classList.add("collapse"); // you define this class in CSS to hide
  }
}

// Initial check
handleWidthChange(mq);

// Listen for changes
mq.addEventListener
  ? mq.addEventListener("change", handleWidthChange)
  : mq.addListener(handleWidthChange); // fallback

//   back to top
//   back to top

const backToTopButton = document.getElementById('backToTop');

// Show/hide button based on scroll position
window.addEventListener('scroll', function() {
  if (window.pageYOffset > 300) {
    backToTopButton.classList.add('visible');
  } else {
    backToTopButton.classList.remove('visible');
  }
});

// Smooth scroll to top when button is clicked
backToTopButton.addEventListener('click', function() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
});

// Keyboard accessibility - allow Enter key to trigger scroll
backToTopButton.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }
});