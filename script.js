const nav = document.querySelector('nav');
  const mobileToggle = document.querySelector('.mobile-toggle');
  const mobileMenu = document.querySelector('#mobile-menu');
  const copyrightYear = document.querySelector('#copyright-year');
  const animatedItems = document.querySelectorAll('.fade-up');

  if (copyrightYear) {
    copyrightYear.textContent = new Date().getFullYear();
  }

  const closeMobileMenu = () => {
    if (!mobileToggle || !mobileMenu) return;
    mobileToggle.setAttribute('aria-expanded', 'false');
    mobileToggle.setAttribute('aria-label', 'Open navigation menu');
    mobileMenu.classList.remove('open');
    mobileMenu.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('menu-open');
  };

  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', () => {
      const isOpen = mobileToggle.getAttribute('aria-expanded') === 'true';
      mobileToggle.setAttribute('aria-expanded', String(!isOpen));
      mobileToggle.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
      mobileMenu.classList.toggle('open', !isOpen);
      mobileMenu.setAttribute('aria-hidden', String(isOpen));
      document.body.classList.toggle('menu-open', !isOpen);
    });

    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', closeMobileMenu);
    });

    window.addEventListener('keydown', event => {
      if (event.key === 'Escape') closeMobileMenu();
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) closeMobileMenu();
    });
  }

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add('visible');
          }, 100);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    animatedItems.forEach(el => observer.observe(el));
  } else {
    animatedItems.forEach(el => el.classList.add('visible'));
  }

  const syncNavPadding = () => {
    if (!nav) return;
    const mobile = window.innerWidth <= 900;
    if (mobile) {
      nav.style.padding = window.innerWidth <= 560 ? '0.5rem 1.25rem' : '0.6rem 2rem';
      return;
    }
    nav.style.padding = window.scrollY > 60 ? '0.4rem 4rem' : '0.65rem 4rem';
  };

  window.addEventListener('scroll', syncNavPadding);
  window.addEventListener('resize', syncNavPadding);
  syncNavPadding();

  // ─── INQUIRY MODAL LOGIC ───
  const modal = document.querySelector('#inquiry-modal');
  const openModalBtns = document.querySelectorAll('.open-modal-btn');
  const closeModalBtn = document.querySelector('#close-modal-btn');
  const inquiryForm = document.querySelector('#inquiry-form');
  const formStatusMsg = document.querySelector('#form-status-msg');
  const packageSelect = document.querySelector('#form-package');

  const openModal = (packageName) => {
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('menu-open');
    if (packageName && packageSelect) {
      packageSelect.value = packageName;
    }
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('menu-open');
    if (formStatusMsg) {
      formStatusMsg.style.display = 'none';
      formStatusMsg.className = 'form-status';
    }
  };

  openModalBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const packageName = btn.getAttribute('data-package');
      openModal(packageName);
    });
  });

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeModal);
  }

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  }

  window.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeModal();
  });

  if (inquiryForm) {
    inquiryForm.addEventListener('submit', (e) => {
      e.preventDefault();

      if (formStatusMsg) {
        formStatusMsg.style.display = 'block';
        formStatusMsg.className = 'form-status';
        formStatusMsg.textContent = 'Sending request...';
      }

      const formData = new FormData(inquiryForm);

      fetch(inquiryForm.action, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          formStatusMsg.className = 'form-status success';
          formStatusMsg.textContent = data.message;
          inquiryForm.reset();
          setTimeout(closeModal, 2500);
        } else {
          formStatusMsg.className = 'form-status error';
          formStatusMsg.textContent = data.message || 'An error occurred. Please try again.';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        formStatusMsg.className = 'form-status error';
        formStatusMsg.textContent = 'Unable to send request. Please check your connection.';
      });
    });
  }

  // ─── HERO BACKGROUND SLIDESHOW (CINEMATIC VIDEO SIMULATION) ───
  const slides = document.querySelectorAll('.hero-slide');
  if (slides.length > 0) {
    let currentSlide = 0;
    const slideInterval = 6000; // 6 seconds per slide

    const nextSlide = () => {
      slides[currentSlide].classList.remove('active');
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add('active');
    };

    setInterval(nextSlide, slideInterval);
  }

  // ─── BACK TO TOP SCROLLING ENGINE ───
  const backToTopBtn = document.querySelector('#back-to-top');
  if (backToTopBtn) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        backToTopBtn.classList.add('visible');
      } else {
        backToTopBtn.classList.remove('visible');
      }
    });
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }