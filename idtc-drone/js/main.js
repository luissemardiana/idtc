/**
 * IDTC — Main Controller & State Management Engine
 * Official Content integrated from IDTC (PT Jogja Creative Production)
 */

// 1. Projects State Data (Real IDTC Portfolio Projects)
let projectsData = window.IDTC_DB_SERVICES && window.IDTC_DB_SERVICES.length > 0 
    ? window.IDTC_DB_SERVICES.map((s, i) => {
        let fullCaseStudy = null;
        let metrics = [];
        try { if (s.full_case_study) fullCaseStudy = JSON.parse(s.full_case_study); } catch (e) {}
        try { if (s.metrics) metrics = JSON.parse(s.metrics); } catch (e) {}
        
        return {
            id: s.id,
            number: String(i + 1).padStart(2, '0'),
            category: s.category || "SERVICES",
            title: s.title,
            subtitle: s.subtitle || s.price || "Layanan Profesional",
            description: s.description,
            image: s.image_url || "https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1400&auto=format&fit=crop",
            fullCaseStudy: fullCaseStudy,
            stats: metrics,
            metrics: metrics
        };
    }) 
    : [
        {id:1,number:'01',category:'GOVERNMENT & AGENCY',title:'DISNAKER SLEMAN TRAINING',subtitle:'Pelatihan Drone Dinas Tenaga Kerja Kab. Sleman',description:'Pelatihan operasional drone dan keselamatan penerbangan bagi aparatur Dinas Tenaga Kerja Kabupaten Sleman untuk efisiensi pengawasan wilayah.',image:'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1400&auto=format&fit=crop',metrics:[]},
        {id:2,number:'02',category:'DISASTER RELIEF',title:'TANGGAP BENCANA',subtitle:'Pelatihan Drone Penanggulangan Bencana',description:'Pelatihan navigasi dan pemetaan udara fast-response bagi tim relawan serta personil penanggulangan bencana alam.',image:'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1400&auto=format&fit=crop',metrics:[]},
        {id:3,number:'03',category:'GEOSPATIAL',title:'DRONE MAPPING',subtitle:'Pelatihan Fotogrametri & Pemetaan Spasial',description:'Pelatihan pembuatan peta ortomosaik 2D/3D, kontur ketinggian, dan pengolahan data GIS untuk tata ruang serta konstruksi.',image:'https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=1400&auto=format&fit=crop',metrics:[]},
        {id:4,number:'04',category:'SMART AGRICULTURE',title:'DRONE PERTANIAN',subtitle:'Pelatihan Precision Agriculture',description:'Pelatihan penerbangan drone multispektral untuk pemantauan vegetasi lahan dan penyemprotan pupuk presisi berbasis data.',image:'https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=1400&auto=format&fit=crop',metrics:[]},
        {id:5,number:'05',category:'ENTERPRISE FLEET',title:'MULTI-DRONE TRAINING',subtitle:'Pelatihan Berbagai Jenis Wahana Drone',description:'Pelatihan praktis pengoperasian berbagai tipe drone FPV, quadcopter, hingga fixed-wing industri dengan standar keselamatan tinggi.',image:'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1400&auto=format&fit=crop',metrics:[]}
    ];

let trainingProgramsData = window.IDTC_DB_TRAINING && window.IDTC_DB_TRAINING.length > 0
    ? window.IDTC_DB_TRAINING.map((t, i) => ({
        id: String(i + 1).padStart(2, '0'),
        title: t.title,
        subtitle: t.subtitle || "Program Pelatihan",
        description: t.description,
        duration: t.duration || "Modul Disesuaikan",
        target: t.target_audience || "Pemula & Profesional",
        features: t.features ? t.features.split('\n').map(f => f.trim()).filter(f => f) : [],
        ctaText: "Diskusikan Modul Ini di WhatsApp"
    }))
    : [
        {id:'01',title:'Basic Flight & Safety Training',subtitle:'Keterampilan Terbang Dasar & SOP Keselamatan',description:'Pelatihan tingkat dasar yang berfokus pada penguasaan kontrol penerbangan manual, pemahaman regulasi penerbangan, serta standar keselamatan penerbangan (SOP).',duration:'Modul Disesuaikan',target:'Pemula, Hobi, & Staff Operasional Instansi',features:['Keterampilan terbang presisi dari tingkat dasar hingga mahir','Fokus pada teknik penerbangan yang aman','Pemahaman prosedur operasional sesuai standar penerbangan','Praktik langsung di lapangan dengan panduan instruktur berpengalaman']},
        {id:'02',title:'Pengoperasian Kamera & Cinematic Footage',subtitle:'Dokumentasi Udara & Visual Stabil',description:'Modul khusus yang dirancang untuk membantu peserta menghasilkan footage sinematik yang stabil, menguasai gerakan kamera udara, serta profil warna profesional.',duration:'Modul Disesuaikan',target:'Fotografer, Videografer, & Dokumentasi Profesional',features:['Teknik pengoperasian kamera udara resolusi tinggi','Membantu peserta menghasilkan footage yang stabil dan halus','Penguasaan sudut pengambilan gambar & pergerakan smooth orbit','Praktik pengambilan gambar di berbagai kondisi pencahayaan']},
        {id:'03',title:'Pelatihan Drone Mapping & Fotogrametri',subtitle:'Pemetaan Spasial 2D/3D & Data Kontur',description:'Pelatihan pemetaan spasial menggunakan drone untuk pembuatan peta ortomosaik 2D, kontur ketinggian, dan model 3D yang akurat untuk lahan & konstruksi.',duration:'Modul Disesuaikan',target:'Surveyor, Komunitas Mahasiswa, & Profesional GIS',features:['Pemasangan dan pengukuran titik kontrol lapangan (GCP)','Perencanaan misi terbang otonom berbasis grid survey','Pengolahan data hasil foto udara menjadi peta Ortomosaik 2D & 3D Mesh','Export data ke software GIS dan CAD']},
        {id:'04',title:'Pelatihan Drone Pertanian Presisi',subtitle:'Smart Farming & Crop Monitoring',description:'Penguasaan drone multispektral untuk analisis kesehatan tanaman (NDVI) serta teknik penyemprotan otomatis di area lahan pertanian.',duration:'Modul Disesuaikan',target:'Praktisi Pertanian, Perkebunan, & Peneliti',features:['Pengoperasian kamera multispektral pemantau vegetasi','Pemetaan indeks kesehatan tanaman (NDVI Analysis)','Kalibrasi dan penerbangan penyemprotan pupuk/pestisida otomatis','Efisiensi penggunaan bahan dan penghematan waktu kerja']},
        {id:'05',title:'Pelatihan Khusus Instansi & Tanggap Bencana',subtitle:'In-House Training & Custom Programs',description:'Pelatihan khusus yang dirancang sesuai kebutuhan organisasi (seperti Disnaker Sleman dan Tim Tanggap Bencana) dengan jadwal dan materi yang disesuaikan.',duration:'Jadwal & Durasi Custom',target:'Instansi Pemerintah, BUMN, & Tim Relawan Bencana',features:['Materi dan durasi pelatihan menyesuaikan kebutuhan organisasi','Penerbangan pemetaan cepat area terdampak bencana (Rapid SAR Mapping)','Simulasi pengawasan wilayah dan dokumentasi instansi','Sertifikat keahlian resmi dari Indonesia Drone Training Center']}
    ];

document.addEventListener('DOMContentLoaded', () => {

    // ==============================================================================
    // 1. SELECTED PROJECTS SHOWCASE ENGINE
    // ==============================================================================
    let activeProjectIndex = 0;
    let autoplayInterval = null;
    let isUserInteracting = false;
    let autoplayDelayTimeout = null;

    const cardsContainer = document.getElementById('projects-cards-container');
    const infoBox = document.getElementById('info-panel-box');
    const infoCategory = document.getElementById('info-panel-category');
    const infoTitle = document.getElementById('info-panel-title');
    const infoSubtitle = document.getElementById('info-panel-subtitle');
    const infoDesc = document.getElementById('info-panel-desc');
    const infoStatsGrid = document.getElementById('info-panel-stats-grid');
    const infoCtaBtn = document.getElementById('info-panel-cta');

    const renderCardsDOM = () => {
        if (!cardsContainer) return;
        cardsContainer.innerHTML = '';

        projectsData.forEach((project, idx) => {
            const card = document.createElement('div');
            card.className = `project-card-item ${idx === activeProjectIndex ? 'active' : ''}`;
            card.dataset.index = idx;

            card.innerHTML = `
                <img src="${project.image}" alt="${project.title}" class="card-bg-image">
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="flex justify-between items-start">
                        <span class="project-number-badge">${project.number}</span>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-primary-orange bg-black/70 px-3 py-1 rounded border border-white/10">${project.category}</span>
                    </div>
                    <div class="project-collapsed-title">${project.title}</div>
                    <div class="project-expanded-info">
                        <span class="text-xs font-semibold text-primary-orange block mb-1 uppercase tracking-wider">${project.subtitle}</span>
                        <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-2 leading-tight uppercase">${project.title}</h3>
                        <p class="text-xs text-slate-300 line-clamp-2 max-w-lg mb-4">${project.description}</p>
                        <button onclick="openCaseStudyModal(${idx})" class="btn-primary py-2.5 px-5 text-xs">
                            <span>View Case Study</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>
            `;

            card.addEventListener('mouseenter', () => {
                stopAutoplay();
                selectProject(idx);
            });

            card.addEventListener('click', () => {
                isUserInteracting = true;
                stopAutoplay();
                selectProject(idx);
            });

            cardsContainer.appendChild(card);
        });

        cardsContainer.addEventListener('mouseleave', () => {
            clearTimeout(autoplayDelayTimeout);
            autoplayDelayTimeout = setTimeout(() => {
                isUserInteracting = false;
                startAutoplay();
            }, 3000);
        });
    };

    const selectProject = (index) => {
        activeProjectIndex = index;
        const current = projectsData[activeProjectIndex];

        const cardElements = document.querySelectorAll('.project-card-item');
        cardElements.forEach((el, idx) => {
            if (idx === activeProjectIndex) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        if (infoBox) {
            infoBox.classList.add('panel-updating');
            setTimeout(() => {
                if (infoCategory) infoCategory.textContent = current.category;
                if (infoTitle) infoTitle.textContent = current.title;
                if (infoSubtitle) infoSubtitle.textContent = current.subtitle;
                if (infoDesc) infoDesc.textContent = current.description;
                if (infoCtaBtn) infoCtaBtn.setAttribute('onclick', `openCaseStudyModal(${activeProjectIndex})`);

                if (infoStatsGrid) {
                    infoStatsGrid.innerHTML = '';
                    current.metrics.forEach(st => {
                        const statItem = document.createElement('div');
                        statItem.className = 'stat-grid-item';
                        statItem.innerHTML = `
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block mb-1">${st.label}</span>
                            <span class="text-sm font-bold text-white leading-tight block">${st.value}</span>
                        `;
                        infoStatsGrid.appendChild(statItem);
                    });
                }
                infoBox.classList.remove('panel-updating');
            }, 150);
        }
    };

    const startAutoplay = () => {
        if (autoplayInterval) clearInterval(autoplayInterval);
        autoplayInterval = setInterval(() => {
            if (!isUserInteracting) {
                const nextIndex = (activeProjectIndex + 1) % projectsData.length;
                selectProject(nextIndex);
            }
        }, 5000);
    };

    const stopAutoplay = () => {
        if (autoplayInterval) clearInterval(autoplayInterval);
    };

    // Mobile Touch Gesture Support
    let touchStartX = 0;
    let touchEndX = 0;
    if (cardsContainer) {
        cardsContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        cardsContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50) {
                selectProject((activeProjectIndex + 1) % projectsData.length);
            } else if (touchEndX - touchStartX > 50) {
                selectProject((activeProjectIndex - 1 + projectsData.length) % projectsData.length);
            }
            startAutoplay();
        }, { passive: true });
    }

    renderCardsDOM();
    selectProject(0);
    startAutoplay();


    // ==============================================================================
    // 2. CASE STUDY MODAL (Direct Viewing without Registration)
    // ==============================================================================
    const caseStudyModal = document.getElementById('case-study-modal');
    const csCategory = document.getElementById('cs-modal-category');
    const csTitle = document.getElementById('cs-modal-title');
    const csSubtitle = document.getElementById('cs-modal-subtitle');
    const csImage = document.getElementById('cs-modal-image');
    const csDesc = document.getElementById('cs-modal-desc');
    const csObjective = document.getElementById('cs-modal-objective');
    const csTech = document.getElementById('cs-modal-tech');
    const csWorkflow = document.getElementById('cs-modal-workflow');
    const csResult = document.getElementById('cs-modal-result');
    const csMetricsGrid = document.getElementById('cs-modal-metrics');

    window.openCaseStudyModal = (index) => {
        const project = projectsData[index] || projectsData[0];
        if (!caseStudyModal) return;

        if (csCategory) csCategory.textContent = project.category;
        if (csTitle) csTitle.textContent = project.title;
        if (csSubtitle) csSubtitle.textContent = project.subtitle;
        if (csImage) csImage.src = project.image;
        if (csDesc) csDesc.textContent = project.description;

        if (project.fullCaseStudy) {
            if (csObjective) csObjective.textContent = project.fullCaseStudy.objective;
            if (csTech) csTech.textContent = project.fullCaseStudy.technology;
            if (csWorkflow) csWorkflow.textContent = project.fullCaseStudy.workflow;
            if (csResult) csResult.textContent = project.fullCaseStudy.results;
        }

        if (csMetricsGrid) {
            csMetricsGrid.innerHTML = '';
            project.metrics.forEach(m => {
                const item = document.createElement('div');
                item.className = 'bg-black/50 border border-white/10 p-3 rounded';
                item.innerHTML = `
                    <span class="text-[9px] font-extrabold text-primary-orange block uppercase tracking-wider">${m.label}</span>
                    <span class="text-xs font-bold text-white block mt-0.5">${m.value}</span>
                `;
                csMetricsGrid.appendChild(item);
            });
        }

        caseStudyModal.classList.remove('hidden');
        caseStudyModal.classList.add('flex');
    };

    window.closeCaseStudyModal = () => {
        if (caseStudyModal) {
            caseStudyModal.classList.add('hidden');
            caseStudyModal.classList.remove('flex');
        }
    };


    // ==============================================================================
    // 3. TABBED TRAINING SELECTOR ENGINE
    // ==============================================================================
    let activeTrainingTab = 0;
    const trainingTabsContainer = document.getElementById('training-tabs-container');
    const trainingDetailCard = document.getElementById('training-detail-card');

    const renderTrainingTabs = () => {
        if (!trainingTabsContainer) return;
        trainingTabsContainer.innerHTML = '';

        trainingProgramsData.forEach((prog, idx) => {
            const tab = document.createElement('div');
            tab.className = `training-tab-item ${idx === activeTrainingTab ? 'active' : ''}`;
            tab.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-heading font-black text-sm text-primary-orange">${prog.id}</span>
                        <h4 class="font-bold text-white text-base">${prog.title}</h4>
                    </div>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            `;

            tab.addEventListener('click', () => {
                activeTrainingTab = idx;
                renderTrainingTabs();
                updateTrainingDetail(idx);
            });

            trainingTabsContainer.appendChild(tab);
        });
    };

    const updateTrainingDetail = (idx) => {
        if (!trainingDetailCard) return;
        const current = trainingProgramsData[idx];

        trainingDetailCard.innerHTML = `
            <div class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-primary-orange block mb-1">${current.subtitle}</span>
                        <h3 class="text-2xl md:text-3xl font-black text-white">${current.title}</h3>
                    </div>
                    <span class="text-xs font-semibold bg-primary-orange/20 text-primary-orange border border-primary-orange/30 px-3 py-1.5 rounded-full">${current.duration}</span>
                </div>

                <p class="text-sm text-slate-300 leading-relaxed">${current.description}</p>

                <div class="p-4 rounded-lg bg-black/40 border border-white/10">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-1">TARGET PESERTA</span>
                    <span class="text-sm font-bold text-white">${current.target}</span>
                </div>

                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-300 mb-3">MATERI KURIKULUM UNGGULAN</h4>
                    <ul class="space-y-2.5">
                        ${current.features.map(f => `
                            <li class="flex items-center gap-2.5 text-xs text-slate-300">
                                <svg class="w-4 h-4 text-primary-orange shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                <span>${f}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="pt-4">
                    <a href="${window.IDTC_WHATSAPP_LINK || 'https://wa.me/6285600604388'}" target="_blank" class="btn-primary w-full py-3.5 flex justify-center items-center gap-2">
                        <span>Diskusikan Modul Ini di WhatsApp</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        `;
    };

    renderTrainingTabs();
    updateTrainingDetail(0);


    // ==============================================================================
    // 4. STICKY NAVBAR SCROLL EFFECT
    // ==============================================================================
    const navbar = document.querySelector('.navbar-sticky');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ==============================================================================
    // 5. MOBILE NAVIGATION DRAWER
    // ==============================================================================
    const mobileMenuBtn = document.getElementById('mobile-menu-toggle');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const closeDrawerBtn = document.getElementById('close-drawer-btn');

    if (mobileMenuBtn && mobileDrawer) {
        const toggleMobileDrawer = () => {
            mobileDrawer.classList.toggle('hidden');
        };

        mobileMenuBtn.addEventListener('click', toggleMobileDrawer);
        if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', toggleMobileDrawer);

        const mobileLinks = mobileDrawer.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileDrawer.classList.add('hidden');
            });
        });
    }

    // ==============================================================================
    // 6. ANIMATED STAT COUNTERS
    // ==============================================================================
    const statCounters = document.querySelectorAll('.stat-number');
    if (statCounters.length > 0) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const targetVal = parseInt(el.getAttribute('data-target') || '0', 10);
                    const suffix = el.getAttribute('data-suffix') || '';
                    let currentVal = 0;
                    const step = Math.max(1, Math.ceil(targetVal / 40));

                    const updateCounter = () => {
                        currentVal += step;
                        if (currentVal >= targetVal) {
                            el.textContent = targetVal + suffix;
                        } else {
                            el.textContent = currentVal + suffix;
                            requestAnimationFrame(updateCounter);
                        }
                    };

                    updateCounter();
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        statCounters.forEach(counter => counterObserver.observe(counter));
    }

    // ==============================================================================
    // 7. FAQ ACCORDION MECHANISM
    // ==============================================================================
    const accordionItems = document.querySelectorAll('.accordion-item');
    accordionItems.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const content = item.querySelector('.accordion-content');

        if (header && content) {
            header.addEventListener('click', () => {
                const isActive = item.classList.contains('active');

                accordionItems.forEach(otherItem => {
                    otherItem.classList.remove('active');
                    const otherContent = otherItem.querySelector('.accordion-content');
                    if (otherContent) otherContent.style.maxHeight = null;
                });

                if (!isActive) {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        }
    });

    // ==============================================================================
    // 8. AI CHATBOT INTEGRATION
    // ==============================================================================
    const chatToggleBtn = document.getElementById('chat-toggle-btn');
    const chatWindow = document.getElementById('chat-window-container');
    const closeChatBtn = document.getElementById('close-chat-btn');
    const chatForm = document.getElementById('chat-form-input');
    const chatInput = document.getElementById('chat-text-field');
    const chatMessagesContainer = document.getElementById('chat-messages-body');

    if (chatToggleBtn && chatWindow) {
        const toggleChat = () => {
            chatWindow.classList.toggle('chat-hidden');
            if (!chatWindow.classList.contains('chat-hidden') && chatInput) {
                chatInput.focus();
            }
        };

        chatToggleBtn.addEventListener('click', toggleChat);
        if (closeChatBtn) closeChatBtn.addEventListener('click', toggleChat);

        const appendChatMessage = (text, isUser = false) => {
            const wrapper = document.createElement('div');
            wrapper.className = `flex ${isUser ? 'justify-end' : 'justify-start'} mb-3`;

            const bubble = document.createElement('div');
            bubble.className = `max-w-[82%] p-3 text-xs leading-relaxed ${isUser ? 'chat-bubble-user' : 'chat-bubble-ai'}`;
            bubble.style.wordBreak = 'break-word';
            bubble.innerHTML = text;

            wrapper.appendChild(bubble);
            chatMessagesContainer.appendChild(wrapper);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        };

        const showTypingIndicator = () => {
            const typingWrapper = document.createElement('div');
            typingWrapper.id = 'chat-typing-wrapper';
            typingWrapper.className = 'flex justify-start mb-3';
            typingWrapper.innerHTML = `
                <div class="chat-bubble-ai p-3 rounded-xl flex items-center gap-1.5">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            chatMessagesContainer.appendChild(typingWrapper);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        };

        const removeTypingIndicator = () => {
            const typingWrapper = document.getElementById('chat-typing-wrapper');
            if (typingWrapper) typingWrapper.remove();
        };

        if (chatForm) {
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const msg = chatInput.value.trim();
                if (!msg) return;

                appendChatMessage(msg, true);
                chatInput.value = '';
                showTypingIndicator();

                try {
                    const res = await fetch('chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: msg })
                    });
                    const data = await res.json();
                    removeTypingIndicator();

                    if (data.status === 'success') {
                        appendChatMessage(data.message);
                    } else {
                        appendChatMessage("Maaf, terjadi masalah: " + data.message);
                    }
                } catch (error) {
                    removeTypingIndicator();
                    appendChatMessage("Maaf, jaringan gagal terhubung ke server chat.");
                }
            });
        }
    }

    document.addEventListener('languageChanged', () => {
        if (typeof renderCardsDOM === 'function') renderCardsDOM();
        if (typeof selectProject === 'function') selectProject(activeProjectIndex || 0);
        if (typeof renderTrainingTabs === 'function') renderTrainingTabs();
        if (typeof updateTrainingDetail === 'function') updateTrainingDetail(activeTrainingTab || 0);

        if (typeof caseStudyModal !== 'undefined' && caseStudyModal && !caseStudyModal.classList.contains('hidden')) {
            if (typeof window.openCaseStudyModal === 'function') window.openCaseStudyModal(activeProjectIndex || 0);
        }
    });
});

// ==============================================================================
// 9. BILINGUAL TRANSLATION ENGINE (ID / EN)
// ==============================================================================
const i18nTranslations = {
    id: {
        "nav-expertise": "Project & Training",
        "nav-projects": "Selected Projects",
        "nav-training": "Training",
        "nav-services": "Services",
        "nav-about": "About",
        "nav-certification": "Certification",
        "nav-gallery": "Gallery",
        "nav-news": "Artikel",
        "nav-credentials": "Credentials",
        "nav-faq": "FAQ",
        "btn-explore-training": "Explore Training",
        "btn-about-idtc": "Tentang IDTC",
        "hero-eyebrow": "INDONESIA DRONE TRAINING CENTER",
        "hero-headline": "<span class=\"hero-headline-line\">INDONESIA</span><span class=\"hero-headline-line\">DRONE</span><span class=\"hero-headline-line\">TRAINING</span><span class=\"hero-headline-line\">CENTER</span>",
        "hero-subtitle": "Professional Drone Training for the Future.",
        "hero-desc": "Layanan pelatihan drone profesional berfokus pada keterampilan terbang, pengoperasian kamera, dan keselamatan penerbangan langsung di lapangan.",
        "about-eyebrow": "ABOUT IDTC",
        "about-headline": "ENGINEERED<br>FOR THE SKY.",
        "about-desc-1": "Indonesia Drone Training Center (IDTC) merupakan unit layanan pelatihan profesional di bawah PT Jogja Creative Production yang berfokus pada keterampilan terbang, pengoperasian kamera, dan keselamatan penerbangan.",
        "about-desc-2": "Peserta belajar langsung melalui praktik di lapangan dengan panduan instruktur berpengalaman, mulai dari tingkat dasar hingga mahir. Pelatihan ini dirancang agar setiap peserta mampu menguasai teknik penerbangan dengan aman, menghasilkan footage yang stabil, serta memahami prosedur operasional sesuai standar industri.",
        "about-metric-1": "Modul Training",
        "about-metric-2": "Alumni Pilot",
        "about-metric-3": "Tahun Pengalaman",
        "about-metric-4": "Instruktur Lisensi",
        "about-btn": "Discover IDTC",
        "expertise-eyebrow": "LAYANAN KAMI",
        "expertise-title": "PROJECT & TRAINING",
        "expertise-desc": "Portofolio proyek dan program pelatihan drone profesional dari IDTC.",
        "tab-projects": "01. PROJECTS",
        "tab-training": "02. TRAINING",
        "tab-services": "03. SERVICES",
        "projects-eyebrow": "PORTOPOLIO REKAM JEJAK",
        "projects-title": "OUR PROJECTS",
        "projects-subtitle": "Hover atau tap untuk menjelajahi rekal jejak pelatihan IDTC. Aktif berganti setiap 5 detik.",
        "projects-metrics-title": "IMPORTANT INFORMATION",
        "projects-btn-modal": "View Project",
        "why-eyebrow": "KEUNGGULAN IDTC",
        "why-title": "WHY IDTC?",
        "why-desc": "Tiga standar utama yang menjadikan setiap program pelatihan IDTC aman, presisi, dan berkelas profesional.",
        "why-card1-num": "[01 / INSTRUCTOR]",
        "why-card1-title": "Instruktur Berpengalaman",
        "why-card1-desc": "Peserta belajar langsung melalui praktik di lapangan dengan panduan instruktur praktisi berpengalaman.",
        "why-card2-num": "[02 / SAFETY FIRST]",
        "why-card2-title": "Safety & SOP Standar",
        "why-card2-desc": "Fokus pada teknik penerbangan yang aman, pemahaman prosedur operasional sesuai standar keselamatan penerbangan.",
        "why-card3-num": "[03 / PRECISION]",
        "why-card3-title": "Footage Stabil & Presisi",
        "why-card3-desc": "Keterampilan pengoperasian kamera canggih untuk menghasilkan visual sinematik yang stabil dan data spasial presisi.",
        "workflow-eyebrow": "ALUR WORKFLOW",
        "workflow-title": "TRAINING PROCESS",
        "training-eyebrow": "PROGRAM & MODUL PELATIHAN",
        "training-title": "TRAINING PROGRAMS",
        "training-desc": "Materi dan durasi pelatihan menyesuaikan kemampuan awal peserta serta kebutuhan organisasi (hobi, dokumentasi profesional, hingga industri).",
        "services-eyebrow": "SOLUSI & LAYANAN",
        "services-title": "IDTC SERVICES",
        "cert-eyebrow": "STANDARISASI & KUALIFIKASI",
        "cert-title": "CERTIFICATION & SAFETY",
        "gallery-eyebrow": "DOKUMENTASI LAPANGAN",
        "gallery-title": "TRAINING GALLERY",
        "testimonials-eyebrow": "REPUTASI & TESTIMONI",
        "testimonials-title": "INDUSTRY FEEDBACK",
        "credentials-eyebrow": "NETWORK & CREDENTIALS",
        "credentials-title": "TRUSTED & RECOGNIZED",
        "credentials-desc": "Building professional drone capabilities through industry standards, qualified training, regulatory compliance, and trusted partnerships.",
        "faq-eyebrow": "INFORMASI UMUM",
        "faq-title": "FREQUENTLY ASKED QUESTIONS",
        "cta-headline": "SIAP MENGUASAI TEKNOLOGI DRONE?",
        "cta-subhead": "Hubungi tim konsultan IDTC hari ini untuk jadwal pelatihan, in-house corporate training, atau konsultasi pemetaan.",
        "cta-btn": "Hubungi Kami via WhatsApp",
        "services-subtitle": "Layanan pemetaan udara, sinematografi, dan inspeksi teknologi tinggi dari IDTC.",
        "services-card1-tag": "01 / MEDIA",
        "services-card1-title": "AERIAL<br>PHOTOGRAPHY",
        "services-card2-tag": "02 / GEOSPATIAL",
        "services-card2-title": "MAPPING &<br>SURVEY",
        "services-card3-tag": "03 / AUDIT",
        "services-card3-title": "INDUSTRIAL<br>INSPECTION",
        "services-card4-tag": "04 / ADVISORY",
        "services-card4-title": "DRONE<br>CONSULTING",
        "workflow-step1": "Persiapan & Teori Dasar",
        "workflow-step2": "Latihan Simulator",
        "workflow-step3": "Praktik Lapangan",
        "workflow-step4": "Teknik Kamera",
        "workflow-step5": "Evaluasi Terbang",
        "workflow-step6": "Sertifikat Resmi",
        "cert-desc": "Sertifikasi IDTC dirancang untuk memastikan setiap peserta menguasai teknik penerbangan yang aman dan mematuhi standar keselamatan operasional di industri.",
        "cert-card-title": "IDTC Remote Pilot Qualification",
        "cert-card-desc": "Penilaian standar terbang mencakup kelaikan wahana, SOP darurat, dan manajemen risiko ruang udara.",
        "cert-card-pill": "Official Certificate of Mastery",
        "gallery-card1-tag": "Outdoor Flight Simulation",
        "gallery-card1-title": "Praktik Penerbangan Presisi Manual & Otonom",
        "gallery-card2-tag": "Ground School Class",
        "gallery-card2-title": "Briefing Regulasi & Navigasi",
        "testimonial-quote1": "\"Pelatihan drone bersama IDTC memberikan pemahaman mendalam tentang keselamatan penerbangan dan teknik kamera yang sangat berguna untuk tugas lapangan.\"",
        "testimonial-name1": "Peserta Pelatihan Disnaker Kab. Sleman",
        "testimonial-role1": "Instansi Pemerintah Daerah",
        "testimonial-quote2": "\"Materi drone mapping dan fotogrametri disampaikan secara terstruktur. Praktik langsung membuat kami paham pemetaan topografi.\"",
        "testimonial-name2": "Komunitas Mahasiswa & Surveyor",
        "testimonial-role2": "Geospatial Mapping Team",
        "testimonial-quote3": "\"Instruktur sangat berpengalaman dan komunikatif. Materi tanggap bencana dan respon cepat sangat relevan untuk simulasi di lapangan.\"",
        "testimonial-name3": "Tim Relawan Penanggulangan Bencana",
        "testimonial-role3": "Disaster Relief Operator",
        "cred-cat1-title": "REGULATORY & AVIATION",
        "cred-org1-name": "DKPPU / Kemenhub RI",
        "cred-org1-rel": "Regulasi & Keselamatan Penerbangan",
        "cred-org2-name": "AirNav Indonesia",
        "cred-org2-rel": "Izin Wilayah Udara & NOTAM",
        "cred-org3-name": "Disnaker Kab. Sleman",
        "cred-org3-rel": "Instansi Mitra In-House Training",
        "cred-cat2-title": "CERTIFICATION & PROFESSIONAL STANDARDS",
        "cred-org4-name": "Sertifikasi BNSP / Pilot",
        "cred-org4-rel": "Standar Kompetensi Pilot Drone",
        "cred-org5-name": "FASI (Aero Sport Indonesia)",
        "cred-org5-rel": "Standar Navigasi & Olahraga Udara",
        "cred-org6-name": "ISO Safety Flight SOP",
        "cred-org6-rel": "Standard Operasional & Prosedur Risk Management",
        "cred-cat3-title": "INDUSTRY & TECHNOLOGY PARTNERS",
        "cred-org7-name": "DJI Enterprise Platform",
        "cred-org7-rel": "Armada Wahana Udara & Sensor",
        "cred-org8-name": "Pix4D & Agisoft Photogrammetry",
        "cred-org8-rel": "Software Pengolahan Data Spasial",
        "cred-org9-name": "PT Jogja Creative Production",
        "cred-org9-rel": "Perusahaan Induk (Parent Enterprise)",
        "cred-pillar1-title": "Regulatory Compliance",
        "cred-pillar1-desc": "Operating in accordance with applicable aviation and drone regulations.",
        "cred-pillar2-title": "Professional Training",
        "cred-pillar2-desc": "Structured training focused on practical skills, flight safety, and professional operations.",
        "cred-pillar3-title": "Certification",
        "cred-pillar3-desc": "Training and assessment aligned with applicable professional competency standards.",
        "cred-pillar4-title": "Partnerships",
        "cred-pillar4-desc": "Working with qualified industry and technology partners.",
        "cred-veri-tag": "VERIFICATION",
        "cred-veri-title": "OFFICIAL CREDENTIALS",
        "cred-veri1-lbl": "Business Registration",
        "cred-veri1-val": "NIB: [Awaiting Data]",
        "cred-veri2-lbl": "Operational License",
        "cred-veri2-val": "[Awaiting Data]",
        "cred-veri3-lbl": "Certification",
        "cred-veri3-val": "[Awaiting Data]",
        "cred-veri-qr-lbl": "Verification",
        "cred-veri-qr-txt": "QR Code",
        "cred-veri-disc": "Credential details will be updated once official documentation is provided. All placeholder entries are clearly marked.",
        "faq-eyebrow": "PERTANYAAN UMUM",
        "faq-title": "FREQUENTLY ASKED QUESTIONS",
        "faq-q1": "Siapa saja yang bisa mengikuti pelatihan drone di IDTC?",
        "faq-a1": "Pelatihan ini dirancang untuk semua kalangan: hobi, dokumentasi profesional, komunitas mahasiswa, instansi pemerintah (seperti Disnaker Sleman), hingga kebutuhan industri pertanian dan konstruksi.",
        "faq-q2": "Bagaimana materi dan durasi pelatihan disesuaikan?",
        "faq-a2": "Materi dan durasi pelatihan menyesuaikan kemampuan awal peserta serta kebutuhan organisasi, baik pelatihan reguler maupun in-house training perusahaan.",
        "footer-desc": "Indonesia Drone Training Center — Layanan pelatihan drone profesional oleh PT Jogja Creative Production.",
        "footer-nav": "Navigasi",
        "footer-contact": "Informasi Kontak Official",
        "footer-company": "Perusahaan:",
        "footer-address": "Alamat:",
        "footer-tagline": "Master The Sky — Indonesia Drone Training Center",
        "modal-desc-title": "DESKRIPSI PROYEK",
        "modal-metric-title": "METRIK UTAMA",
        "modal-obj-title": "TUJUAN PROYEK",
        "modal-tech-title": "TEKNOLOGI & FLEET",
        "modal-flow-title": "ALUR WORKFLOW",
        "modal-res-title": "HASIL AKHIR & IMPACT",
        "modal-close-btn": "Close Case Study",
        "chat-title": "IDTC Assistant",
        "chat-subtitle": "AI Assistant",
        "chat-greeting": "Halo! 👋<br>Saya IDTC Assistant dari PT Jogja Creative Production. Ada yang ingin Anda tanyakan seputar pelatihan drone atau layanan aviasi kami?"
    },
    en: {
        "nav-expertise": "Project & Training",
        "nav-projects": "Selected Projects",
        "nav-training": "Training Programs",
        "nav-services": "Services",
        "nav-about": "About IDTC",
        "nav-certification": "Certification",
        "nav-gallery": "Gallery",
        "nav-news": "Articles & News",
        "nav-credentials": "Credentials",
        "nav-faq": "FAQ",
        "btn-explore-training": "Explore Training",
        "btn-about-idtc": "About IDTC",
        "hero-eyebrow": "INDONESIA DRONE TRAINING CENTER",
        "hero-headline": "<span class=\"hero-headline-line\">INDONESIA</span><span class=\"hero-headline-line\">DRONE</span><span class=\"hero-headline-line\">TRAINING</span><span class=\"hero-headline-line\">CENTER</span>",
        "hero-subtitle": "Professional Drone Training for the Future.",
        "hero-desc": "Professional drone training services focusing on flight skills, camera operations, and flight safety directly in the field.",
        "about-eyebrow": "ABOUT IDTC",
        "about-headline": "ENGINEERED<br>FOR THE SKY.",
        "about-desc-1": "Indonesia Drone Training Center (IDTC) is a professional training unit under PT Jogja Creative Production focusing on flight skills, camera operations, and flight safety.",
        "about-desc-2": "Participants learn directly through hands-on field practice guided by experienced instructors, from basic to advanced levels. Designed to enable participants to master safe flight techniques, produce stable footage, and adhere to industry operational procedures.",
        "about-metric-1": "Training Modules",
        "about-metric-2": "Pilot Alumni",
        "about-metric-3": "Years Experience",
        "about-metric-4": "Licensed Instructors",
        "about-btn": "Discover IDTC",
        "expertise-eyebrow": "OUR SERVICES",
        "expertise-title": "PROJECT & TRAINING",
        "expertise-desc": "Project portfolio and professional drone training programs from IDTC.",
        "tab-projects": "01. PROJECTS",
        "tab-training": "02. TRAINING",
        "tab-services": "03. SERVICES",
        "projects-eyebrow": "TRACK RECORD PORTFOLIO",
        "projects-title": "OUR PROJECTS",
        "projects-subtitle": "Hover or tap to explore IDTC's training track record. Automatically changes every 5 seconds.",
        "projects-metrics-title": "IMPORTANT INFORMATION",
        "projects-btn-modal": "View Project",
        "why-eyebrow": "WHY CHOOSE IDTC",
        "why-title": "WHY IDTC?",
        "why-desc": "Three core standards that make every IDTC training program safe, precise, and professionally world-class.",
        "why-card1-num": "[01 / INSTRUCTOR]",
        "why-card1-title": "Experienced Instructors",
        "why-card1-desc": "Participants learn directly through field practice guided by experienced practitioner instructors.",
        "why-card2-num": "[02 / SAFETY FIRST]",
        "why-card2-title": "Safety & Standard SOP",
        "why-card2-desc": "Focusing on safe flight techniques and understanding operational procedures adhering to aviation safety standards.",
        "why-card3-num": "[03 / PRECISION]",
        "why-card3-title": "Stable & Precise Footage",
        "why-card3-desc": "Advanced camera operation skills to produce stable cinematic visuals and precise spatial data.",
        "workflow-eyebrow": "WORKFLOW PROCESS",
        "workflow-title": "TRAINING PROCESS",
        "training-eyebrow": "MODULES & CURRICULUM",
        "training-title": "TRAINING PROGRAMS",
        "training-desc": "Training materials and duration are customized to participants' initial abilities and organizational requirements (hobby, professional documentation, or industrial mapping).",
        "services-eyebrow": "SOLUTIONS & SERVICES",
        "services-title": "IDTC SERVICES",
        "cert-eyebrow": "STANDARDS & QUALIFICATIONS",
        "cert-title": "CERTIFICATION & SAFETY",
        "gallery-eyebrow": "FIELD DOCUMENTATION",
        "gallery-title": "TRAINING GALLERY",
        "testimonials-eyebrow": "REPUTATION & FEEDBACK",
        "testimonials-title": "INDUSTRY FEEDBACK",
        "credentials-eyebrow": "NETWORK & CREDENTIALS",
        "credentials-title": "TRUSTED & RECOGNIZED",
        "credentials-desc": "Building professional drone capabilities through industry standards, qualified training, regulatory compliance, and trusted partnerships.",
        "faq-eyebrow": "GENERAL INFORMATION",
        "faq-title": "FREQUENTLY ASKED QUESTIONS",
        "cta-headline": "READY TO MASTER DRONE TECHNOLOGY?",
        "cta-subhead": "Contact IDTC consultants today for training schedules, in-house corporate training, or mapping consultations.",
        "cta-btn": "Contact Us via WhatsApp",
        "services-subtitle": "High-tech aerial mapping, cinematography, and inspection services from IDTC.",
        "services-card1-tag": "01 / MEDIA",
        "services-card1-title": "AERIAL<br>PHOTOGRAPHY",
        "services-card2-tag": "02 / GEOSPATIAL",
        "services-card2-title": "MAPPING &<br>SURVEY",
        "services-card3-tag": "03 / AUDIT",
        "services-card3-title": "INDUSTRIAL<br>INSPECTION",
        "services-card4-tag": "04 / ADVISORY",
        "services-card4-title": "DRONE<br>CONSULTING",
        "workflow-step1": "Preparation & Basic Theory",
        "workflow-step2": "Simulator Training",
        "workflow-step3": "Field Practice",
        "workflow-step4": "Camera Techniques",
        "workflow-step5": "Flight Evaluation",
        "workflow-step6": "Official Certification",
        "cert-desc": "IDTC certification is designed to ensure every participant masters safe flight techniques and complies with operational safety standards in the industry.",
        "cert-card-title": "IDTC Remote Pilot Qualification",
        "cert-card-desc": "Standard flight assessment includes airworthiness, emergency SOPs, and airspace risk management.",
        "cert-card-pill": "Official Certificate of Mastery",
        "gallery-card1-tag": "Outdoor Flight Simulation",
        "gallery-card1-title": "Manual & Autonomous Precision Flight Practice",
        "gallery-card2-tag": "Ground School Class",
        "gallery-card2-title": "Regulation & Navigation Briefing",
        "testimonial-quote1": "\"Drone training with IDTC provides an in-depth understanding of flight safety and camera techniques that are very useful for field duties.\"",
        "testimonial-name1": "Sleman Regency Manpower Office Trainee",
        "testimonial-role1": "Local Government Agency",
        "testimonial-quote2": "\"Drone mapping and photogrammetry materials were delivered in a structured manner. Direct practice made us understand topographic mapping.\"",
        "testimonial-name2": "Student Community & Surveyors",
        "testimonial-role2": "Geospatial Mapping Team",
        "testimonial-quote3": "\"Instructors are highly experienced and communicative. Disaster response material is very relevant for field simulations.\"",
        "testimonial-name3": "Disaster Relief Volunteer Team",
        "testimonial-role3": "Disaster Relief Operator",
        "cred-cat1-title": "REGULATORY & AVIATION",
        "cred-org1-name": "DKPPU / Ministry of Transportation RI",
        "cred-org1-rel": "Regulation & Flight Safety",
        "cred-org2-name": "AirNav Indonesia",
        "cred-org2-rel": "Airspace Permit & NOTAM",
        "cred-org3-name": "Sleman Regency Manpower Office",
        "cred-org3-rel": "In-House Training Partner Agency",
        "cred-cat2-title": "CERTIFICATION & PROFESSIONAL STANDARDS",
        "cred-org4-name": "BNSP Certification / Pilot",
        "cred-org4-rel": "Drone Pilot Competency Standard",
        "cred-org5-name": "FASI (Indonesia Aero Sport)",
        "cred-org5-rel": "Navigation & Air Sports Standard",
        "cred-org6-name": "ISO Safety Flight SOP",
        "cred-org6-rel": "Standard Operations & Risk Management Procedures",
        "cred-cat3-title": "INDUSTRY & TECHNOLOGY PARTNERS",
        "cred-org7-name": "DJI Enterprise Platform",
        "cred-org7-rel": "Aerial Fleet & Sensors",
        "cred-org8-name": "Pix4D & Agisoft Photogrammetry",
        "cred-org8-rel": "Spatial Data Processing Software",
        "cred-org9-name": "PT Jogja Creative Production",
        "cred-org9-rel": "Parent Enterprise",
        "cred-pillar1-title": "Regulatory Compliance",
        "cred-pillar1-desc": "Operating in accordance with applicable aviation and drone regulations.",
        "cred-pillar2-title": "Professional Training",
        "cred-pillar2-desc": "Structured training focused on practical skills, flight safety, and professional operations.",
        "cred-pillar3-title": "Certification",
        "cred-pillar3-desc": "Training and assessment aligned with applicable professional competency standards.",
        "cred-pillar4-title": "Partnerships",
        "cred-pillar4-desc": "Working with qualified industry and technology partners.",
        "cred-veri-tag": "VERIFICATION",
        "cred-veri-title": "OFFICIAL CREDENTIALS",
        "cred-veri1-lbl": "Business Registration",
        "cred-veri1-val": "NIB: [Awaiting Data]",
        "cred-veri2-lbl": "Operational License",
        "cred-veri2-val": "[Awaiting Data]",
        "cred-veri3-lbl": "Certification",
        "cred-veri3-val": "[Awaiting Data]",
        "cred-veri-qr-lbl": "Verification",
        "cred-veri-qr-txt": "QR Code",
        "cred-veri-disc": "Credential details will be updated once official documentation is provided. All placeholder entries are clearly marked.",
        "faq-eyebrow": "GENERAL INFORMATION",
        "faq-title": "FREQUENTLY ASKED QUESTIONS",
        "faq-q1": "Who can participate in IDTC training?",
        "faq-a1": "This training is designed for everyone: hobbyists, professional documentation, student communities, government agencies (like Sleman Manpower Office), to agricultural and construction industry needs.",
        "faq-q2": "How are the training material and duration adjusted?",
        "faq-a2": "Training material and duration are tailored to participants' initial abilities and organizational needs, both for regular training and corporate in-house training.",
        "footer-desc": "Indonesia Drone Training Center — Professional drone training services by PT Jogja Creative Production.",
        "footer-nav": "Navigation",
        "footer-contact": "Official Contact Information",
        "footer-company": "Company:",
        "footer-address": "Address:",
        "footer-tagline": "Master The Sky — Indonesia Drone Training Center",
        "modal-desc-title": "PROJECT DESCRIPTION",
        "modal-metric-title": "KEY METRICS",
        "modal-obj-title": "PROJECT OBJECTIVE",
        "modal-tech-title": "TECHNOLOGY & FLEET",
        "modal-flow-title": "WORKFLOW PROCESS",
        "modal-res-title": "FINAL RESULT & IMPACT",
        "modal-close-btn": "Close Case Study",
        "chat-title": "IDTC Assistant",
        "chat-subtitle": "AI Assistant",
        "chat-greeting": "Hello! 👋<br>I am the IDTC Assistant from PT Jogja Creative Production. Is there anything you would like to ask about our drone training or aviation services?"
    }
};

let currentLanguage = localStorage.getItem('idtc_lang') || 'id';

window.switchLanguage = function (lang) {
    if (lang !== 'id' && lang !== 'en') return;
    currentLanguage = lang;
    localStorage.setItem('idtc_lang', lang);
    applyLanguage();
};

function applyLanguage() {
    const lang = currentLanguage;
    const dict = i18nTranslations[lang] || i18nTranslations['id'];

    // Database content is language-neutral. Keep it as the source of truth; only
    // use legacy language arrays when a project explicitly provides them.
    if (typeof projectsData_id !== 'undefined' && typeof projectsData_en !== 'undefined') projectsData = lang === 'id' ? projectsData_id : projectsData_en;
    if (typeof trainingProgramsData_id !== 'undefined' && typeof trainingProgramsData_en !== 'undefined') trainingProgramsData = lang === 'id' ? trainingProgramsData_id : trainingProgramsData_en;

    document.dispatchEvent(new Event('languageChanged'));

    // 1. Update text elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) {
            el.innerHTML = dict[key];
        }
    });

    // 2. Update button states (Desktop & Mobile)
    const btnId = document.getElementById('lang-btn-id');
    const btnEn = document.getElementById('lang-btn-en');
    const mobBtnId = document.getElementById('mob-lang-btn-id');
    const mobBtnEn = document.getElementById('mob-lang-btn-en');

    if (lang === 'id') {
        if (btnId) { btnId.className = "px-2.5 py-1 rounded text-white bg-primary-orange transition-all font-bold"; }
        if (btnEn) { btnEn.className = "px-2.5 py-1 rounded text-slate-400 hover:text-white transition-all font-bold"; }
        if (mobBtnId) { mobBtnId.className = "px-2.5 py-1 rounded text-white bg-primary-orange transition-all font-bold"; }
        if (mobBtnEn) { mobBtnEn.className = "px-2.5 py-1 rounded text-slate-400 hover:text-white transition-all font-bold"; }
    } else {
        if (btnId) { btnId.className = "px-2.5 py-1 rounded text-slate-400 hover:text-white transition-all font-bold"; }
        if (btnEn) { btnEn.className = "px-2.5 py-1 rounded text-white bg-primary-orange transition-all font-bold"; }
        if (mobBtnId) { mobBtnId.className = "px-2.5 py-1 rounded text-slate-400 hover:text-white transition-all font-bold"; }
        if (mobBtnEn) { mobBtnEn.className = "px-2.5 py-1 rounded text-white bg-primary-orange transition-all font-bold"; }
    }
}

// Initial application of language on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    applyLanguage();

    document.addEventListener('languageChanged', () => {
        if (typeof renderCardsDOM === 'function') renderCardsDOM();
        if (typeof selectProject === 'function') selectProject(activeProjectIndex || 0);
        if (typeof renderTrainingTabs === 'function') renderTrainingTabs();
        if (typeof updateTrainingDetail === 'function') updateTrainingDetail(activeTrainingTab || 0);

        const csModal = document.getElementById('case-study-modal');
        if (csModal && !csModal.classList.contains('hidden')) {
            if (typeof openCaseStudyModal === 'function') openCaseStudyModal(activeProjectIndex || 0);
        }
    });

    // Expertise Tabs Logic
    const expertiseTabs = document.querySelectorAll('.expertise-tab-btn');
    const expertisePanes = document.querySelectorAll('.expertise-content-pane');

    expertiseTabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab');

            // Reset all buttons
            expertiseTabs.forEach(b => {
                b.classList.remove('active', 'bg-primary-orange', 'text-white', 'shadow-lg', 'shadow-primary-orange/20');
                b.classList.add('bg-white/5', 'text-slate-200');
                const dot = b.querySelector('.marker-dot');
                if (dot) dot.classList.add('hidden');
            });

            // Activate clicked button
            btn.classList.remove('bg-white/5', 'text-slate-200');
            btn.classList.add('active', 'bg-primary-orange', 'text-white', 'shadow-lg', 'shadow-primary-orange/20');
            const dot = btn.querySelector('.marker-dot');
            if (dot) dot.classList.remove('hidden');

            // Switch panes
            expertisePanes.forEach(pane => {
                pane.classList.add('hidden', 'opacity-0');
            });
            const activePane = document.getElementById('tab-content-' + target);
            if (activePane) {
                activePane.classList.remove('hidden');
                // Slight delay for fade-in effect
                setTimeout(() => {
                    activePane.classList.remove('opacity-0');
                }, 50);
            }
        });
    });

    window.openTrainingSection = (event) => {
        if (event?.currentTarget?.dataset?.heroLink === 'primary' && event.currentTarget.getAttribute('href') !== '#tab-content-training') return;
        event?.preventDefault();
        const trainingTab = document.querySelector('.expertise-tab-btn[data-tab="training"]');
        const trainingPane = document.getElementById('tab-content-training');
        if (!trainingTab || !trainingPane) return;

        // Simpan section terpilih di URL agar setelah refresh tetap di sini, tidak kembali ke hero.
        try {
            history.replaceState(null, '', '#tab-content-training');
        } catch (e) { /* abaikan */ }

        trainingTab.click();
        window.setTimeout(() => {
            trainingPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 80);
    };
});

const adminModal = document.getElementById('admin-secret-modal');
const adminPasswordInput = document.getElementById('admin-password-input');
const adminPasswordError = document.getElementById('admin-password-error');
const adminPortalView = document.getElementById('admin-portal-view');
const adminCancelBtn = document.getElementById('admin-cancel-btn');
const adminLoginBtn = document.getElementById('admin-login-btn');
const adminCloseViewBtn = document.getElementById('admin-close-view-btn');

function openAdminModal() {
    if (!adminModal) return;
    adminModal.classList.remove('hidden');
    adminModal.classList.add('flex');
    adminPasswordError?.classList.add('hidden');
    adminPasswordError?.classList.remove('admin-modal-shake');
    if (adminPasswordInput) {
        adminPasswordInput.value = '';
        setTimeout(() => adminPasswordInput.focus(), 50);
    }
}

function closeAdminModal() {
    if (!adminModal) return;
    adminModal.classList.add('hidden');
    adminModal.classList.remove('flex');
    adminPasswordError?.classList.add('hidden');
    adminPasswordError?.classList.remove('admin-modal-shake');
    if (adminPasswordInput) {
        adminPasswordInput.value = '';
    }
}

// ==============================================================================
// IDTC CONTENT STUDIO & ARTICLE ENGINE
// ==============================================================================

window.isAdminLoggedIn = false;
window.csrfToken = '';
let currentAdminArticles = [];
let currentAdminFilter = 'all';
let calendarCursor = getJakartaToday();

function getJakartaToday() {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: 'numeric',
        day: 'numeric'
    }).formatToParts(new Date());
    const values = Object.fromEntries(parts.map(part => [part.type, Number(part.value)]));
    return new Date(values.year, values.month - 1, values.day);
}

function getArticleDateKey(value) {
    return value ? String(value).slice(0, 10) : '';
}

function formatCalendarDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    }).format(new Date(Date.UTC(year, month - 1, day, 5)));
}

function renderArticleCalendar() {
    const grid = document.getElementById('article-calendar-grid');
    const monthLabel = document.getElementById('calendar-month-label');
    const agenda = document.getElementById('calendar-agenda-list');
    const count = document.getElementById('calendar-event-count');
    if (!grid || !monthLabel || !agenda) return;

    const year = calendarCursor.getFullYear();
    const month = calendarCursor.getMonth();
    const monthName = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(calendarCursor);
    monthLabel.textContent = monthName.toUpperCase();

    const events = currentAdminArticles
        .map(article => ({
            article,
            dateKey: getArticleDateKey(article.status === 'scheduled' ? article.scheduled_at : article.published_at)
        }))
        .filter(event => event.dateKey.startsWith(`${year}-${String(month + 1).padStart(2, '0')}-`))
        .sort((first, second) => first.dateKey.localeCompare(second.dateKey));
    const eventsByDate = events.reduce((groups, event) => {
        (groups[event.dateKey] ||= []).push(event.article);
        return groups;
    }, {});

    const firstDayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = getJakartaToday();
    const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    grid.innerHTML = '';

    for (let index = 0; index < firstDayOffset; index += 1) {
        grid.appendChild(document.createElement('span'));
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayButton = document.createElement('button');
        const dayEvents = eventsByDate[dateKey] || [];
        dayButton.type = 'button';
        dayButton.dataset.calendarDate = dateKey;
        dayButton.className = `article-calendar-day ${dateKey === todayKey ? 'is-today' : ''} ${dayEvents.length ? 'has-events' : ''}`;
        dayButton.innerHTML = `<span>${day}</span>${dayEvents.length ? `<i>${dayEvents.length}</i>` : ''}`;
        dayButton.title = dayEvents.length ? `${dayEvents.length} artikel pada ${formatCalendarDate(dateKey)}` : `Pilih ${formatCalendarDate(dateKey)}`;
        dayButton.addEventListener('click', () => selectCalendarDate(dateKey));
        grid.appendChild(dayButton);
    }

    count.textContent = String(events.length);
    agenda.innerHTML = events.length ? events.map(({ article, dateKey }) => `
        <button type="button" class="article-calendar-agenda-item" data-article-id="${article.id}">
            <span class="article-calendar-agenda-date">${formatCalendarDate(dateKey)} WIB</span>
            <strong>${escapeHtml(article.title)}</strong>
            <small class="${article.status === 'published' ? 'is-published' : ''}">${article.status === 'published' ? 'PUBLISHED' : 'TERJADWAL'}</small>
        </button>
    `).join('') : '<p class="text-[#8A7B58]">Belum ada agenda artikel pada bulan ini.</p>';

    agenda.querySelectorAll('[data-article-id]').forEach(button => {
        button.addEventListener('click', () => loadArticleIntoEditor(Number(button.dataset.articleId)));
    });
}

function selectCalendarDate(dateKey) {
    const statusSelect = document.getElementById('article-form-status');
    const scheduleWrap = document.getElementById('scheduled-datetime-wrap');
    const scheduleInput = document.getElementById('article-form-scheduled-at');
    if (!statusSelect || !scheduleWrap || !scheduleInput) return;

    const today = getJakartaToday();
    const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    if (dateKey < todayKey) {
        notifyToast('Tanggal yang dipilih sudah lewat. Pilih tanggal hari ini atau tanggal setelahnya.', 'warning');
        return;
    }

    statusSelect.value = 'scheduled';
    scheduleWrap.classList.remove('hidden');
    scheduleInput.value = `${dateKey}T09:00`;
    document.getElementById('article-form-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    scheduleInput.focus();
}

function setupArticleCalendar() {
    document.getElementById('calendar-prev-btn')?.addEventListener('click', () => {
        calendarCursor.setMonth(calendarCursor.getMonth() - 1);
        renderArticleCalendar();
    });
    document.getElementById('calendar-next-btn')?.addEventListener('click', () => {
        calendarCursor.setMonth(calendarCursor.getMonth() + 1);
        renderArticleCalendar();
    });
    document.getElementById('calendar-today-btn')?.addEventListener('click', () => {
        calendarCursor = getJakartaToday();
        renderArticleCalendar();
    });
    renderArticleCalendar();
}

// Initialize Content Studio & Public Articles
document.addEventListener('DOMContentLoaded', () => {
    checkAdminSession();
    loadPublicArticles();
    setupArticleFormListeners();
    setupArticleCalendar();
});

// Check Admin Session Status
async function checkAdminSession() {
    try {
        const res = await fetch('api/auth.php?action=check');
        const data = await res.json();
        const studioButtons = [
            document.getElementById('nav-admin-studio-btn'),
            document.getElementById('mobile-nav-admin-studio-btn')
        ].filter(Boolean);

        if (data.status === 'success' && data.authenticated) {
            window.isAdminLoggedIn = true;
            window.csrfToken = data.csrf_token || '';
            studioButtons.forEach(button => button.classList.remove('hidden'));
            // The article studio now has its own admin URL. This flag is used
            // only by that page's embedded studio, never by regular visitors.
            if (new URLSearchParams(window.location.search).get('article_studio') === '1') {
                showAdminPortal();
            }
        } else {
            window.isAdminLoggedIn = false;
            studioButtons.forEach(button => button.classList.add('hidden'));
        }
    } catch (e) {
        console.warn('Auth check error:', e);
    }
}

// Open Admin Studio or Trigger Login Modal
function openAdminStudioOrLogin() {
    if (window.isAdminLoggedIn) {
        showAdminPortal();
    } else {
        openAdminModal();
    }
}

function openAdminModal() {
    const adminSecretModal = document.getElementById('admin-secret-modal');
    const adminPasswordInput = document.getElementById('admin-password-input');
    const adminPasswordError = document.getElementById('admin-password-error');
    if (adminSecretModal) adminSecretModal.classList.remove('hidden');
    if (adminPasswordInput) {
        adminPasswordInput.value = '';
        adminPasswordInput.focus();
    }
    if (adminPasswordError) notifyInline(adminPasswordError, '');
}

function closeAdminModal() {
    const adminSecretModal = document.getElementById('admin-secret-modal');
    if (adminSecretModal) adminSecretModal.classList.add('hidden');
}

function showAdminPortal() {
    closeAdminModal();
    const adminPortalView = document.getElementById('admin-portal-view');
    if (adminPortalView) {
        adminPortalView.classList.remove('hidden');
        loadAdminArticles(currentAdminFilter);
        try { sessionStorage.setItem('idtc_studio_open', '1'); } catch (e) {}
    }
}

function hideAdminPortal() {
    const adminPortalView = document.getElementById('admin-portal-view');
    if (adminPortalView) {
        adminPortalView.classList.add('hidden');
        try { sessionStorage.setItem('idtc_studio_open', '0'); } catch (e) {}
    }
}

// Reopen the Content Studio automatically after a reload if it was open.
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (new URLSearchParams(window.location.search).get('article_studio') === '1' && window.IDTC_ADMIN_LOGGED_IN && document.getElementById('admin-portal-view')) {
            showAdminPortal();
        }
    } catch (e) { /* sessionStorage unavailable */ }
});

// Handle Admin Login via Backend API
async function handleAdminLoginSubmit() {
    const passwordInput = document.getElementById('admin-password-input');
    const passwordError = document.getElementById('admin-password-error');

    const password = passwordInput ? passwordInput.value.trim() : '';

    const shakePanel = () => {
        const panel = document.querySelector('#admin-secret-modal .admin-modal-panel');
        if (!panel) return;
        panel.classList.remove('admin-modal-shake');
        void panel.offsetWidth;
        panel.classList.add('admin-modal-shake');
    };

    if (!password) {
        if (passwordError) notifyInline(passwordError, 'Password wajib diisi.', 'error');
        shakePanel();
        return;
    }

    try {
        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
            window.isAdminLoggedIn = true;
            window.csrfToken = data.csrf_token || '';
            ['nav-admin-studio-btn', 'mobile-nav-admin-studio-btn'].forEach(id => {
                document.getElementById(id)?.classList.remove('hidden');
            });
            notifyToast('Login berhasil. Membuka Content Studio…', 'success');
            setTimeout(() => { showAdminPortal(); }, 400);
        } else {
            if (passwordError) notifyInline(passwordError, data.message || 'Login gagal. Periksa username/password.', 'error');
            shakePanel();
        }
    } catch (e) {
        if (passwordError) notifyInline(passwordError, 'Koneksi server gagal.', 'error');
        shakePanel();
    }
}

// Handle Logout
async function handleAdminLogoutSubmit() {
    try {
        await fetch('api/auth.php?action=logout', {
            method: 'POST',
            headers: { 'X-CSRF-Token': window.csrfToken }
        });
    } catch (e) {}

    window.isAdminLoggedIn = false;
    window.csrfToken = '';
    ['nav-admin-studio-btn', 'mobile-nav-admin-studio-btn'].forEach(id => {
        document.getElementById(id)?.classList.add('hidden');
    });
    hideAdminPortal();
    loadPublicArticles();
}

// Setup Event Listeners for Auth & Form
function setupArticleFormListeners() {
    const adminCancelBtn = document.getElementById('admin-cancel-btn');
    const adminLoginBtn = document.getElementById('admin-login-btn');
    const adminCloseViewBtn = document.getElementById('admin-close-view-btn');
    const adminPasswordInput = document.getElementById('admin-password-input');
    const btnLogout = document.getElementById('btn-admin-logout');
    const btnNewArticleMode = document.getElementById('btn-new-article-mode');

    if (adminCancelBtn) adminCancelBtn.addEventListener('click', closeAdminModal);
    if (adminLoginBtn) adminLoginBtn.addEventListener('click', handleAdminLoginSubmit);
    if (adminCloseViewBtn) adminCloseViewBtn.addEventListener('click', hideAdminPortal);
    if (btnLogout) btnLogout.addEventListener('click', handleAdminLogoutSubmit);
    if (btnNewArticleMode) btnNewArticleMode.addEventListener('click', resetArticleForm);

    const categorySelect = document.getElementById('article-form-category');
    if (categorySelect) {
        categorySelect.addEventListener('change', toggleCustomCategoryInput);
        toggleCustomCategoryInput();
    }

    if (adminPasswordInput) {
        adminPasswordInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handleAdminLoginSubmit();
        });
    }

    // Status Select Listener
    const statusSelect = document.getElementById('article-form-status');
    const scheduledWrap = document.getElementById('scheduled-datetime-wrap');

    if (statusSelect && scheduledWrap) {
        statusSelect.addEventListener('change', () => {
            if (statusSelect.value === 'scheduled') {
                scheduledWrap.classList.remove('hidden');
                const scheduledInput = document.getElementById('article-form-scheduled-at');
                if (scheduledInput && !scheduledInput.value) {
                    const now = new Date();
                    now.setHours(now.getHours() + 1);
                    now.setMinutes(0);
                    const iso = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                    scheduledInput.value = iso;
                }
            } else {
                scheduledWrap.classList.add('hidden');
            }
        });
    }

    // Image File Upload Handler
    const imageFileInput = document.getElementById('article-form-image-file');
    const imageUrlInput = document.getElementById('article-form-image-url');

    if (imageFileInput) {
        imageFileInput.addEventListener('change', async () => {
            if (imageFileInput.files && imageFileInput.files[0]) {
                const file = imageFileInput.files[0];
                const formData = new FormData();
                formData.append('image', file);

                try {
                    const res = await fetch('api/articles.php?action=upload_image', {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': window.csrfToken },
                        body: formData
                    });
                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                        if (imageUrlInput) imageUrlInput.value = data.image_url;
                        showImagePreview(data.image_url);
                        notifyToast('Gambar berhasil diupload.', 'success');
                    } else {
                        notifyToast(data.message || 'Gagal mengupload gambar.', 'error');
                    }
                } catch (e) {
                    notifyToast('Gagal mengupload gambar ke server.', 'error');
                }
            }
        });
    }

    if (imageUrlInput) {
        imageUrlInput.addEventListener('input', () => {
            showImagePreview(imageUrlInput.value.trim());
        });
    }

    // Submit Buttons
    const btnSaveDraft = document.getElementById('btn-save-draft');
    const btnSchedule = document.getElementById('btn-schedule-article');
    const btnPublishNow = document.getElementById('btn-publish-now');
    const btnCancelEdit = document.getElementById('btn-cancel-edit');
    const btnUnpublish = document.getElementById('btn-unpublish-article');
    const btnDelete = document.getElementById('btn-delete-article');
    const btnPreviewDraft = document.getElementById('btn-preview-draft');

    if (btnSaveDraft) btnSaveDraft.addEventListener('click', () => submitArticleForm('draft'));
    if (btnSchedule) btnSchedule.addEventListener('click', () => submitArticleForm('scheduled'));
    if (btnPublishNow) btnPublishNow.addEventListener('click', () => submitArticleForm('published'));
    if (btnCancelEdit) btnCancelEdit.addEventListener('click', resetArticleForm);
    if (btnPreviewDraft) btnPreviewDraft.addEventListener('click', openAdminPreviewModal);

    if (btnUnpublish) {
        btnUnpublish.addEventListener('click', () => {
            const articleId = document.getElementById('article-form-id').value;
            if (articleId && articleId !== '0') {
                triggerUnpublishArticle(articleId);
            }
        });
    }

    if (btnDelete) {
        btnDelete.addEventListener('click', () => {
            const articleId = document.getElementById('article-form-id').value;
            if (articleId && articleId !== '0') {
                triggerDeleteArticle(articleId);
            }
        });
    }

    // Search Input Listener
    const adminSearchInput = document.getElementById('admin-search-input');
    if (adminSearchInput) {
        adminSearchInput.addEventListener('input', () => {
            filterAdminArticles(currentAdminFilter);
        });
    }
}

function toggleCustomCategoryInput() {
    const categorySelect = document.getElementById('article-form-category');
    const customCategoryWrap = document.getElementById('article-form-custom-category-wrap');
    const customCategoryInput = document.getElementById('article-form-custom-category');
    const isCustomCategory = categorySelect?.value === 'Lainnya';

    if (customCategoryWrap) customCategoryWrap.classList.toggle('hidden', !isCustomCategory);
    if (customCategoryInput) customCategoryInput.required = isCustomCategory;
}

function getArticleCategory() {
    const category = document.getElementById('article-form-category').value.trim();
    if (category !== 'Lainnya') return category;
    return document.getElementById('article-form-custom-category').value.trim();
}

// Show Image Thumbnail Preview
function showImagePreview(url) {
    const wrap = document.getElementById('image-preview-wrap');
    const img = document.getElementById('image-preview-img');
    if (url) {
        if (img) img.src = url;
        if (wrap) wrap.classList.remove('hidden');
    } else {
        if (wrap) wrap.classList.add('hidden');
    }
}

// Submit Article Form (Create or Update)
async function submitArticleForm(targetStatus) {
    const id = document.getElementById('article-form-id').value || '0';
    const title = document.getElementById('article-form-title').value.trim();
    const category = getArticleCategory();
    const author = document.getElementById('article-form-author').value.trim();
    const imageUrl = document.getElementById('article-form-image-url').value.trim();
    const summary = document.getElementById('article-form-summary').value.trim();
    const content = document.getElementById('article-form-content').value.trim();
    const scheduledAt = document.getElementById('article-form-scheduled-at').value;

    if (!title) { notifyToast('Judul Artikel wajib diisi.', 'error'); return; }
    if (!category) { notifyToast('Silakan tulis kategori artikel.', 'error'); return; }
    if (!content) { notifyToast('Isi Artikel lengkap wajib diisi.', 'error'); return; }

    if (targetStatus === 'scheduled') {
        if (!scheduledAt) {
            notifyToast('Silakan tentukan Waktu Publish (WIB) untuk artikel Terjadwal.', 'error');
            return;
        }
    }

    const payload = {
        id: parseInt(id, 10),
        title,
        category,
        author,
        image_url: imageUrl,
        summary,
        content,
        status: targetStatus,
        scheduled_at: scheduledAt
    };

    const action = (id === '0' || !id) ? 'create' : 'update';

    try {
        const res = await fetch(`api/articles.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
            notifyToast(data.message || 'Artikel berhasil disimpan!', 'success');
            resetArticleForm();
            loadAdminArticles(currentAdminFilter);
            loadPublicArticles();
        } else {
            notifyToast(data.message || 'Gagal menyimpan artikel.', 'error');
        }
    } catch (e) {
        notifyToast('Terjadi kesalahan koneksi ke API server.', 'error');
    }
}

// Reset Article Form to New Article Mode
function resetArticleForm() {
    document.getElementById('article-form-id').value = '0';
    document.getElementById('article-form-title').value = '';
    document.getElementById('article-form-author').value = 'Tim Media IDTC';
    document.getElementById('article-form-category').selectedIndex = 0;
    document.getElementById('article-form-custom-category').value = '';
    toggleCustomCategoryInput();
    document.getElementById('article-form-image-url').value = '';
    document.getElementById('article-form-image-file').value = '';
    document.getElementById('article-form-summary').value = '';
    document.getElementById('article-form-content').value = '';
    document.getElementById('article-form-status').value = 'draft';
    document.getElementById('article-form-scheduled-at').value = '';

    showImagePreview('');

    document.getElementById('form-mode-title').textContent = 'TULIS ARTIKEL BARU.';
    document.getElementById('form-mode-badge').textContent = 'MODE DRAFT';
    document.getElementById('scheduled-datetime-wrap').classList.add('hidden');

    document.getElementById('btn-cancel-edit').classList.add('hidden');
    document.getElementById('btn-unpublish-article').classList.add('hidden');
    document.getElementById('btn-delete-article').classList.add('hidden');
}

// Load Admin Articles List
async function loadAdminArticles(status = 'all') {
    currentAdminFilter = status;
    const searchInput = document.getElementById('admin-search-input');
    const search = searchInput ? searchInput.value.trim() : '';

    try {
        const res = await fetch(`api/articles.php?action=list&status=${status}&search=${encodeURIComponent(search)}`);
        const data = await res.json();

        if (data.status === 'success') {
            currentAdminArticles = data.data || [];
            renderAdminArticlesTable(currentAdminArticles);
            renderArticleCalendar();

            const studioCount = document.getElementById('studio-total-count');
            if (studioCount) {
                studioCount.textContent = `(${data.count} Terdaftar)`;
            }
        }
    } catch (e) {
        console.error('Failed to fetch admin articles:', e);
    }
}

// Filter Admin Articles Button Trigger
function filterAdminArticles(status) {
    currentAdminFilter = status;
    ['all', 'draft', 'scheduled', 'published'].forEach(s => {
        const btn = document.getElementById(`filter-btn-${s}`);
        if (btn) {
            if (s === status) {
                btn.className = 'rounded-lg px-3 py-1.5 text-[#111111] bg-white shadow-sm transition';
            } else {
                btn.className = 'rounded-lg px-3 py-1.5 text-[#6A5B3A] hover:text-[#111111] transition';
            }
        }
    });

    loadAdminArticles(status);
}

// Render Admin Table Rows
function renderAdminArticlesTable(articles) {
    const tbody = document.getElementById('admin-articles-table-body');
    const emptyState = document.getElementById('admin-empty-state');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!articles || articles.length === 0) {
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    articles.forEach(art => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-[#FBFAF7] transition';

        let badgeHtml = '';
        if (art.status === 'published') {
            badgeHtml = `<span class="inline-block rounded-md bg-emerald-100 px-3 py-1.5 text-xs font-black uppercase text-emerald-800">PUBLISHED<br><span class="font-normal text-[11px]">${art.formatted_published_at}</span></span>`;
        } else if (art.status === 'scheduled') {
            badgeHtml = `<span class="inline-block rounded-md bg-amber-100 px-3 py-1.5 text-xs font-black uppercase text-amber-900">TERJADWAL<br><span class="font-normal text-[11px]">${art.formatted_scheduled_at}</span></span>`;
        } else {
            badgeHtml = `<span class="inline-block rounded-md bg-slate-200 px-3 py-1.5 text-xs font-black uppercase text-slate-800">DRAFT</span>`;
        }

        const coverImg = art.image_url 
            ? `<img src="${art.image_url}" class="h-16 w-20 rounded-lg object-cover border border-[#E3DCCB]" />`
            : `<div class="h-16 w-20 rounded-lg bg-slate-100 border border-[#E3DCCB] flex items-center justify-center text-[10px] text-slate-400 font-bold">NO COVER</div>`;

        tr.innerHTML = `
            <td class="px-4 py-3">${coverImg}</td>
            <td class="px-4 py-3 max-w-xs">
                <p class="font-bold text-[#111111] text-sm line-clamp-2">${escapeHtml(art.title)}</p>
                <p class="text-xs text-[#6A5B3A] mt-0.5">Oleh: ${escapeHtml(art.author)} • Slug: /${escapeHtml(art.slug)}</p>
            </td>
            <td class="px-4 py-3 font-semibold text-sm text-[#111111]">${escapeHtml(art.category)}</td>
            <td class="px-4 py-3">${badgeHtml}</td>
            <td class="px-4 py-3 text-xs text-slate-600">${art.formatted_created_at}</td>
            <td class="px-4 py-3 text-right space-x-1">
                <button onclick="loadArticleIntoEditor(${art.id})" class="rounded-md border border-[#111111] bg-white px-3 py-1.5 text-xs font-bold uppercase text-[#111111] hover:bg-[#111111] hover:text-white transition">Edit</button>
                <button onclick="openAdminPreviewModalById(${art.id})" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold uppercase text-slate-700 hover:bg-slate-100 transition">Preview</button>
                ${art.status === 'published' 
                    ? `<button onclick="triggerUnpublishArticle(${art.id})" class="rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs font-bold uppercase text-amber-800 hover:bg-amber-100 transition">Tarik</button>`
                    : `<button onclick="quickPublishArticle(${art.id})" class="rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-xs font-bold uppercase text-emerald-800 hover:bg-emerald-100 transition">Publish</button>`
                }
                <button onclick="triggerDeleteArticle(${art.id})" class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-bold uppercase text-red-600 hover:bg-red-100 transition">Hapus</button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

// Load Article into Form for Editing
async function loadArticleIntoEditor(id) {
    try {
        const res = await fetch(`api/articles.php?action=detail&id=${id}`);
        const data = await res.json();

        if (data.status === 'success' && data.data) {
            const art = data.data;

            document.getElementById('article-form-id').value = art.id;
            document.getElementById('article-form-title').value = art.title;
            const categorySelect = document.getElementById('article-form-category');
            const customCategoryInput = document.getElementById('article-form-custom-category');
            const hasPresetCategory = Array.from(categorySelect.options).some(option => option.value === art.category && option.value !== 'Lainnya');
            categorySelect.value = hasPresetCategory ? art.category : 'Lainnya';
            customCategoryInput.value = hasPresetCategory ? '' : art.category;
            toggleCustomCategoryInput();
            document.getElementById('article-form-author').value = art.author;
            document.getElementById('article-form-image-url').value = art.image_url || '';
            document.getElementById('article-form-summary').value = art.summary || '';
            document.getElementById('article-form-content').value = art.content || '';
            document.getElementById('article-form-status').value = art.status;

            if (art.status === 'scheduled' && art.scheduled_at) {
                const dt = new Date(art.scheduled_at);
                const iso = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                document.getElementById('article-form-scheduled-at').value = iso;
                document.getElementById('scheduled-datetime-wrap').classList.remove('hidden');
            } else {
                document.getElementById('scheduled-datetime-wrap').classList.add('hidden');
            }

            showImagePreview(art.image_url);

            // Update UI for Edit Mode
            document.getElementById('form-mode-title').textContent = `EDIT ARTIKEL #${art.id}`;
            document.getElementById('form-mode-badge').textContent = `MODE EDIT (${art.status.toUpperCase()})`;

            document.getElementById('btn-cancel-edit').classList.remove('hidden');
            document.getElementById('btn-delete-article').classList.remove('hidden');

            if (art.status === 'published') {
                document.getElementById('btn-unpublish-article').classList.remove('hidden');
            } else {
                document.getElementById('btn-unpublish-article').classList.add('hidden');
            }

            // Scroll to Form
            document.getElementById('article-form-card').scrollIntoView({ behavior: 'smooth' });
        }
    } catch (e) {
        notifyToast('Gagal memuat detail artikel.', 'error');
    }
}

// Quick Publish Action
async function quickPublishArticle(id) {
    try {
        const res = await fetch('api/articles.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
            body: JSON.stringify({ id, status: 'published' })
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            notifyToast('Artikel berhasil dipublikasikan.', 'success');
            loadAdminArticles(currentAdminFilter);
            loadPublicArticles();
        } else {
            notifyToast(data.message || 'Gagal mempublikasikan artikel.', 'error');
        }
    } catch (e) {
        notifyToast('Koneksi gagal.', 'error');
    }
}

// Trigger Unpublish Article Confirmation
function triggerUnpublishArticle(id) {
    openConfirmModal({
        title: 'Tarik Artikel dari Website?',
        subtitle: 'Status artikel akan diubah menjadi Draft.',
        message: 'Artikel akan ditarik dari tampilan publik dan dikembalikan ke status Draft. Seluruh data artikel tetap tersimpan aman.',
        actionText: 'Ya, Tarik Artikel',
        actionClass: 'bg-amber-600 hover:bg-amber-700',
        onConfirm: async () => {
            try {
                const res = await fetch('api/articles.php?action=unpublish', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    notifyToast('Artikel ditarik dari publikasi.', 'success');
                    resetArticleForm();
                    loadAdminArticles(currentAdminFilter);
                    loadPublicArticles();
                } else {
                    notifyToast(data.message || 'Gagal menarik artikel.', 'error');
                }
            } catch (e) {
                notifyToast('Gagal memproses penarikan artikel.', 'error');
            }
        }
    });
}

// Trigger Delete Article Confirmation
function triggerDeleteArticle(id) {
    openConfirmModal({
        title: 'Hapus Artikel Permanen?',
        subtitle: 'Tindakan ini tidak dapat dibatalkan.',
        message: 'Apakah Anda yakin ingin menghapus artikel ini secara permanen dari database?',
        actionText: 'Ya, Hapus Permanen',
        actionClass: 'bg-red-600 hover:bg-red-700',
        onConfirm: async () => {
            try {
                const res = await fetch('api/articles.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    notifyToast('Artikel berhasil dihapus.', 'success');
                    resetArticleForm();
                    loadAdminArticles(currentAdminFilter);
                    loadPublicArticles();
                } else {
                    notifyToast(data.message || 'Gagal menghapus artikel.', 'error');
                }
            } catch (e) {
                notifyToast('Gagal menghapus artikel.', 'error');
            }
        }
    });
}

// Universal Confirmation Modal Logic
function openConfirmModal(config) {
    const modal = document.getElementById('admin-confirm-modal');
    const titleEl = document.getElementById('confirm-modal-title');
    const subtitleEl = document.getElementById('confirm-modal-subtitle');
    const messageEl = document.getElementById('confirm-modal-message');
    const actionBtn = document.getElementById('confirm-modal-action');
    const cancelBtn = document.getElementById('confirm-modal-cancel');

    if (titleEl) titleEl.textContent = config.title || 'Konfirmasi';
    if (subtitleEl) subtitleEl.textContent = config.subtitle || 'IDTC System';
    if (messageEl) messageEl.textContent = config.message || 'Lanjutkan tindakan?';
    if (actionBtn) {
        actionBtn.textContent = config.actionText || 'Ya, Lanjutkan';
        actionBtn.className = `rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md ${config.actionClass || 'bg-red-600 hover:bg-red-700'}`;
        
        actionBtn.onclick = () => {
            closeConfirmModal();
            if (config.onConfirm) config.onConfirm();
        };
    }
    if (cancelBtn) {
        cancelBtn.onclick = closeConfirmModal;
    }

    if (modal) modal.classList.remove('hidden');
}

function closeConfirmModal() {
    const modal = document.getElementById('admin-confirm-modal');
    if (modal) modal.classList.add('hidden');
}

// Preview Modal Logic
function openAdminPreviewModal() {
    const title = document.getElementById('article-form-title').value.trim() || 'Judul Artikel (Draft)';
    const category = getArticleCategory() || 'Kategori';
    const author = document.getElementById('article-form-author').value || 'Admin';
    const summary = document.getElementById('article-form-summary').value || '';
    const content = document.getElementById('article-form-content').value || '';
    const imageUrl = document.getElementById('article-form-image-url').value.trim();
    const status = document.getElementById('article-form-status').value;

    document.getElementById('prev-modal-title').textContent = title;
    document.getElementById('prev-modal-category').textContent = category;
    document.getElementById('prev-modal-meta').textContent = `Penulis: ${author} • WIB Status: ${status.toUpperCase()}`;
    document.getElementById('prev-modal-summary').textContent = summary || 'Belum ada ringkasan.';
    document.getElementById('prev-modal-content').textContent = content || 'Belum ada isi artikel.';

    const img = document.getElementById('prev-modal-image');
    if (imageUrl) {
        img.src = imageUrl;
        img.classList.remove('hidden');
    } else {
        img.classList.add('hidden');
    }

    const previewModal = document.getElementById('admin-preview-modal');
    if (previewModal) {
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }
}

function openAdminPreviewModalById(id) {
    const art = currentAdminArticles.find(a => a.id === id);
    if (!art) return;

    document.getElementById('prev-modal-title').textContent = art.title;
    document.getElementById('prev-modal-category').textContent = art.category;
    document.getElementById('prev-modal-meta').textContent = `Penulis: ${art.author} • ${art.formatted_created_at}`;
    document.getElementById('prev-modal-summary').textContent = art.summary || '';
    document.getElementById('prev-modal-content').textContent = art.content || '';

    const img = document.getElementById('prev-modal-image');
    if (art.image_url) {
        img.src = art.image_url;
        img.classList.remove('hidden');
    } else {
        img.classList.add('hidden');
    }

    const previewModal = document.getElementById('admin-preview-modal');
    if (previewModal) {
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }
}

function closeAdminPreviewModal() {
    const modal = document.getElementById('admin-preview-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// -----------------------------------------------------------------------------
// PUBLIC VISITOR ARTICLES ENGINE
// -----------------------------------------------------------------------------
async function loadPublicArticles() {
    const grid = document.getElementById('public-news-grid');
    if (!grid) return;

    try {
        const res = await fetch('api/articles.php?action=list&status=published');
        const data = await res.json();

        if (data.status === 'success' && data.data && data.data.length > 0) {
            const moreLink = document.getElementById('public-news-more');
            if (moreLink) moreLink.classList.toggle('hidden', data.data.length <= 3);
            renderPublicArticles(data.data.slice(0, 3));
        } else {
            document.getElementById('public-news-more')?.classList.add('hidden');
            grid.innerHTML = `
                <div class="col-span-full py-16 text-center border border-white/10 rounded-2xl bg-dark-surface/50">
                    <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mx-auto text-primary-orange mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Belum Ada Artikel Dipublikasikan</h3>
                    <p class="text-sm text-slate-400 mt-2 max-w-md mx-auto">Artikel dan berita terbaru dari PT Drone Trainee Center akan segera hadir di sini.</p>
                </div>
            `;
        }
    } catch (e) {
        console.error('Failed to load public articles:', e);
    }
}

function renderPublicArticles(articles) {
    const grid = document.getElementById('public-news-grid');
    if (!grid) return;

    grid.innerHTML = '';

    articles.forEach(art => {
        const card = document.createElement('a');
        card.className = 'news-card group block no-underline rounded-2xl border border-white/10 bg-dark-surface p-5 transition-all hover:border-primary-orange/50 hover:bg-dark-elevated flex flex-col justify-between cursor-pointer';
        card.href = `artikel_detail.php?slug=${encodeURIComponent(art.slug)}`;
        card.target = '_blank';
        card.rel = 'noopener';

        const coverImg = art.image_url 
            ? `<img src="${art.image_url}" alt="${escapeHtml(art.title)}" class="w-full h-48 object-cover rounded-xl border border-white/10 group-hover:scale-105 transition-transform duration-300" loading="lazy" />`
            : `<div class="w-full h-48 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs text-slate-500 font-bold">IDTC NEWS COVER</div>`;

        card.innerHTML = `
            <div>
                <div class="overflow-hidden rounded-xl mb-4">
                    ${coverImg}
                </div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="rounded-md bg-primary-orange/10 border border-primary-orange/30 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-primary-orange">${escapeHtml(art.category)}</span>
                    <span class="text-[11px] font-medium text-slate-400">${art.formatted_published_at || art.formatted_created_at}</span>
                </div>
                <h3 class="text-lg font-bold text-white group-hover:text-primary-orange transition-colors line-clamp-2 leading-snug">${escapeHtml(art.title)}</h3>
            </div>
            <div class="art-reveal">
                <div class="art-reveal-inner">
                    <p class="text-xs text-slate-300 line-clamp-3 leading-relaxed mb-4">${escapeHtml(art.summary || art.content)}</p>
                    <div class="pt-4 border-t border-white/5 flex items-center justify-between text-xs font-bold text-primary-orange group-hover:translate-x-1 transition-transform">
                        <span>Baca Selengkapnya</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </div>
                </div>
            </div>
        `;

        grid.appendChild(card);
    });
}

// Open Public Article Modal
async function openPublicArticleModal(slugOrId) {
    try {
        const res = await fetch(`api/articles.php?action=detail&slug=${encodeURIComponent(slugOrId)}`);
        const data = await res.json();

        if (data.status === 'success' && data.data) {
            const art = data.data;

            document.getElementById('pub-modal-category').textContent = art.category;
            document.getElementById('pub-modal-date').textContent = art.formatted_published_at || art.formatted_created_at;
            document.getElementById('pub-modal-title').textContent = art.title;
            document.getElementById('pub-modal-author').textContent = art.author;
            
            const summaryBox = document.getElementById('pub-modal-summary-box');
            if (art.summary) {
                summaryBox.textContent = `"${art.summary}"`;
                summaryBox.classList.remove('hidden');
            } else {
                summaryBox.classList.add('hidden');
            }

            document.getElementById('pub-modal-content').textContent = art.content;

            const imgWrap = document.getElementById('pub-modal-image-wrap');
            const img = document.getElementById('pub-modal-image');

            if (art.image_url) {
                img.src = art.image_url;
                imgWrap.classList.remove('hidden');
            } else {
                imgWrap.classList.add('hidden');
            }

            document.getElementById('public-article-modal').classList.remove('hidden');
        } else {
            notifyToast(data.message || 'Artikel tidak ditemukan.', 'error');
        }
    } catch (e) {
        notifyToast('Gagal memuat artikel.', 'error');
    }
}

function closePublicArticleModal() {
    const modal = document.getElementById('public-article-modal');
    if (modal) modal.classList.add('hidden');
}

// Utility: HTML Escape String
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const contactNameInput = document.getElementById('contact-name');
const contactEmailInput = document.getElementById('contact-email');
const contactMessageInput = document.getElementById('contact-message');
const contactSendBtn = document.getElementById('contact-send-btn');

if (contactSendBtn) {
    contactSendBtn.addEventListener('click', () => {
        const name = (contactNameInput && contactNameInput.value ? contactNameInput.value.trim() : '');
        const email = (contactEmailInput && contactEmailInput.value ? contactEmailInput.value.trim() : '');
        const message = (contactMessageInput && contactMessageInput.value ? contactMessageInput.value.trim() : '');

        if (!name && !email && !message) {
            return;
        }

        const subject = encodeURIComponent('Project Inquiry dari ' + (name || 'Client'));
        const body = encodeURIComponent(
            'Nama: ' + (name || '-') + '\n' +
            'Email: ' + (email || '-') + '\n\n' +
            'Kebutuhan:\n' + (message || '-')
        );

        window.location.href = 'mailto:jogjacreativeproduction@gmail.com?subject=' + subject + '&body=' + body;
    });
}
