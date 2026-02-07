const form = document.getElementById("searchForm");
const input = document.getElementById("studentIdInput");
const loading = document.getElementById("loadingMessage");
const menuToggle = document.getElementById("menuToggle");
const mainNav = document.querySelector(".main-nav");
const printResultBtn = document.getElementById("printResultBtn");
const downloadResultBtn = document.getElementById("downloadResultBtn");

// If you open the HTML file via `file:` protocol, `backend/verify.php`
// cannot be reached relative to a server. To force a backend base URL
// set `window.BACKEND_BASE = 'http://localhost/uap'` in the page before
// this script runs (or via the browser console).
const backendBase =
  typeof window !== "undefined" && window.BACKEND_BASE
    ? String(window.BACKEND_BASE).replace(/\/+$/, "")
    : "";
const apiEndpoint = backendBase
  ? `${backendBase}/backend/verify.php`
  : "backend/verify.php";
const fallbackDataEndpoint = backendBase
  ? `${backendBase}/data/students.json`
  : "data/students.json";
const resultLogoUrl = backendBase
  ? `${backendBase}/assets/uap.png`
  : new URL("assets/uap.png", window.location.href).href;
const embeddedFallbackRecords = [
  {
    studentId: "2181011017",
    regNo: "UU18108521",
    name: "RANA AHMED",
    dateOfBirth: "10.02.2001",
    department: "Civil Engineering",
    degree: "Bachelor of Science in Civil Engineering",
    cgpa: "3.70",
    year: "2023",
    certNo: "22429",
    photo: "uploads/students/2181011017.jpg",
  },
  {
    studentId: "2181011018",
    regNo: "UU18108522",
    name: "RAHAT ISLAM",
    dateOfBirth: "25.08.2001",
    department: "Computer Science & Engineering",
    degree: "Bachelor of Science in CSE",
    cgpa: "3.45",
    year: "2022",
    certNo: "22430",
    photo: "uploads/students/63718627.jpg",
  },
  {
    studentId: "63718627",
    regNo: "63718627",
    name: "Habibur Rahman",
    dateOfBirth: "12.03.2000",
    department: "Computer Science & Engineering",
    degree: "Bachelor of Science in Computer Science & Engineering",
    cgpa: "3.85",
    year: "2023",
    certNo: "5734",
    photo: "uploads/students/63718627.jpg",
  },
];

let fallbackRecordsPromise = null;
let currentRecord = null;

function toSafeString(value) {
  if (value === null || value === undefined) return "";
  return String(value).trim();
}

function normalizeQuery(value) {
  return toSafeString(value).toLowerCase();
}

function normalizeRecord(record) {
  let photoPath = toSafeString(record.photo || record.photoPath).replace(
    /\\/g,
    "/",
  );
  if (photoPath !== "" && !/^https?:\/\//i.test(photoPath)) {
    if (photoPath.startsWith("uploads/students/")) {
      // already valid
    } else if (
      !photoPath.includes("/") &&
      /\.(jpe?g|png|webp)$/i.test(photoPath)
    ) {
      photoPath = "uploads/students/" + photoPath;
    } else {
      photoPath = "";
    }
  }
  return {
    studentId: toSafeString(record.studentId || record.student_id),
    regNo: toSafeString(record.regNo || record.registration_no),
    name: toSafeString(record.name),
    dateOfBirth: toSafeString(record.dateOfBirth || record.date_of_birth),
    department: toSafeString(record.department),
    degree: toSafeString(record.degree),
    cgpa: toSafeString(record.cgpa),
    year: toSafeString(record.year || record.passing_year),
    certNo: toSafeString(record.certNo || record.certificate_no),
    photo: photoPath,
  };
}

function renderRecord(record) {
  currentRecord = record;

  // Populate result sheet
  const resultSection = document.getElementById("resultSection");
  if (resultSection) {
    document.getElementById("resultStudentName").textContent = record.name;
    document.getElementById("resultStudentDegree").textContent = record.degree;
    document.getElementById("resultStudentIDDisplay").textContent =
      record.studentId;
    document.getElementById("resultRegNoDisplay").textContent = record.regNo;
    document.getElementById("resultDOBDisplay").textContent =
      record.dateOfBirth;
    document.getElementById("resultYearDisplay").textContent = record.year;
    document.getElementById("resultDegreeDisplay").textContent = record.degree;
    document.getElementById("resultCGPADisplay").textContent = record.cgpa;
    document.getElementById("resultCertNoDisplay").textContent =
      "#" + record.certNo;
    document.getElementById("resultCertCode").textContent = `UAP-${record.year}-\n${record.certNo}`;

    const today = new Date();
    const dateStr = today.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
    document.getElementById("resultVerifiedDate").textContent = dateStr;

    // Handle result photo
    const resultPhotoDisplay = document.getElementById("resultPhotoDisplay");
    const resultPhotoFallback = document.getElementById("resultPhotoFallback");
    if (resultPhotoDisplay && resultPhotoFallback) {
      if (record.photo) {
        resultPhotoDisplay.onload = function () {
          resultPhotoDisplay.style.display = "block";
          resultPhotoFallback.style.display = "none";
        };
        resultPhotoDisplay.onerror = function () {
          resultPhotoDisplay.removeAttribute("src");
          resultPhotoDisplay.style.display = "none";
          resultPhotoFallback.style.display = "flex";
        };

        resultPhotoDisplay.style.display = "none";
        resultPhotoFallback.style.display = "flex";
        resultPhotoDisplay.src = resolveMediaUrl(record.photo);
      } else {
        resultPhotoDisplay.removeAttribute("src");
        resultPhotoDisplay.style.display = "none";
        resultPhotoFallback.style.display = "flex";
      }
    }

    resultSection.classList.remove("hidden");
  }
}

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function resolveMediaUrl(path) {
  const value = toSafeString(path);
  if (!value) return "";

  // Keep absolute URLs (http/https), and also `data:` / `blob:` / `file:` URLs.
  if (/^[a-z][a-z0-9+.-]*:/i.test(value)) return value;

  // When the UI is hosted separately from PHP, allow overriding the base.
  if (backendBase) {
    return `${backendBase}/${value.replace(/^\/+/, "")}`;
  }

  return new URL(value, window.location.href).href;
}

function buildResultHtml(record) {
  const photoUrl = record.photo ? resolveMediaUrl(record.photo) : "";
  const codeText =
    record.year && record.certNo ? `UAP-${record.year}-\n${record.certNo}` : "";

  const verifiedOn = new Date().toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });

  const photoHtml = photoUrl
    ? `<img class="photo" src="${escapeHtml(photoUrl)}" alt="Student photo">`
    : `<div class="photo-fallback">Photo not available</div>`;

  return `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verification Result</title>
    <style>
      :root{--ink:#111827;--muted:#6b7280;--blue:#1e40af;--line:#e5e7eb;}
      *{box-sizing:border-box;}
      body{margin:0;background:#fff;color:var(--ink);font-family:Poppins,Inter,Arial,sans-serif;padding:24px;}
      .paper{position:relative;overflow:hidden;max-width:860px;margin:0 auto;border:1px solid #e3e7f2;border-top:5px solid #1a237e;border-radius:14px;box-shadow:0 10px 30px rgba(17,24,39,.1);padding:42px 52px;}
      .wm{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:640px;opacity:.4;filter:grayscale(1) saturate(0) brightness(1.6);pointer-events:none;user-select:none;}
      .head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;border-bottom:2px solid #0f172a;padding-bottom:22px;margin-bottom:28px;}
      .brand{display:flex;align-items:center;gap:14px;}
      .mono{width:64px;height:64px;border-radius:999px;background:#0f2b6b;color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;letter-spacing:.5px;flex:0 0 auto;}
      h1{margin:0;font-size:28px;line-height:1.12;font-weight:900;text-transform:uppercase;letter-spacing:.6px;color:#0b1324;}
      .sub{margin:6px 0 0;font-size:14px;font-weight:600;color:#2f5fab;letter-spacing:.3px;}
      .code{display:none;text-align:right;}
      .code .label{margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);}
      .code .value{margin:6px 0 0;font-size:18px;font-weight:900;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace;white-space:pre-line;}
      .body{display:flex;gap:28px;margin-bottom:34px;}
      .frame{width:160px;height:200px;background:#f3f4f6;border:1px solid #d1d5db;padding:6px;flex:0 0 auto;display:flex;align-items:center;justify-content:center;}
      .photo{width:100%;height:100%;object-fit:cover;display:block;}
      .photo-fallback{font-size:12px;font-weight:600;color:var(--muted);text-align:center;padding:10px;}
      .summary{flex:1 1 auto;min-width:0;}
      h2{margin:0;font-size:32px;font-weight:900;text-transform:uppercase;letter-spacing:-.02em;}
      .deg{margin:8px 0 0;font-size:18px;font-weight:600;color:var(--blue);}
      .meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 26px;margin-top:18px;}
      .meta .k{display:block;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin-bottom:4px;}
      .meta .v{display:block;font-size:15px;font-weight:900;}
      .meta .v.big{font-size:18px;}
      .perfTitle{margin:0 0 12px;font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:.16em;color:#9aa3b2;border-bottom:1px solid var(--line);padding-bottom:8px;}
      .card{border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff;}
      .row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;padding:16px 18px;border-bottom:1px solid #f1f5f9;align-items:center;}
      .row:last-child{border-bottom:none;}
      .row.hi{background:#eff6ff;border-left:4px solid #2563eb;}
      .row .l{font-size:14px;font-weight:600;color:#4b5563;}
      .row.hi .l{font-weight:900;text-transform:uppercase;color:var(--blue);}
      .row .r{text-align:right;font-size:14px;font-weight:900;}
      .row .r.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace;}
      .cgpa{text-align:right;font-size:36px;font-weight:900;color:var(--blue);line-height:1;}
      .status{display:inline-flex;justify-content:flex-end;align-items:center;gap:8px;font-weight:900;color:#16a34a;text-transform:uppercase;}
      .dot{width:10px;height:10px;border-radius:999px;background:#22c55e;}
      .foot{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;padding-top:26px;margin-top:26px;}
      .verified{text-align:right;}
      .verified .k{margin:0;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:var(--muted);}
      .verified .v{margin:6px 0 0;font-size:12px;font-weight:600;color:#4b5563;}

      @media (min-width:768px){.code{display:block;}}
      @media (max-width:768px){
        body{padding:14px;}
        .paper{padding:28px 20px;border-radius:10px;}
        .wm{width:400px;}
        .head{flex-direction:column;}
        .body{flex-direction:column;align-items:center;}
        h2{text-align:center;font-size:26px;}
        .deg{text-align:center;}
        .meta{grid-template-columns:1fr;}
        .foot{flex-direction:column;align-items:stretch;}
        .verified{text-align:center;}
      }
      @media print{body{padding:0;} .paper{box-shadow:none;border:none;border-radius:0;max-width:none;padding:20px;}}
    </style>
  </head>
  <body>
    <div class="paper">
      <img class="wm" src="${escapeHtml(resultLogoUrl)}" alt="" aria-hidden="true">
      <div class="head">
        <div class="brand">
          <div class="mono" aria-hidden="true">U</div>
          <div>
            <h1>University of Asia Pacific</h1>
            <div class="sub">Official Verification Portal</div>
          </div>
        </div>
        <div class="code">
          <div class="label">Verification Code</div>
          <div class="value">${escapeHtml(codeText)}</div>
        </div>
      </div>

      <div class="body">
        <div class="frame">${photoHtml}</div>
        <div class="summary">
          <h2>${escapeHtml(record.name)}</h2>
          <div class="deg">${escapeHtml(record.degree)}</div>

          <div class="meta">
            <div><span class="k">Student ID</span><span class="v big">${escapeHtml(record.studentId)}</span></div>
            <div><span class="k">Registration No</span><span class="v big">${escapeHtml(record.regNo)}</span></div>
            <div><span class="k">Date of Birth</span><span class="v">${escapeHtml(record.dateOfBirth)}</span></div>
            <div><span class="k">Passing Year</span><span class="v">${escapeHtml(record.year)}</span></div>
          </div>
        </div>
      </div>

      <div class="perf">
        <div class="perfTitle">Academic Performance</div>
        <div class="card">
          <div class="row"><div class="l">Degree Awarded</div><div class="r">${escapeHtml(record.degree)}</div></div>
          <div class="row"><div class="l">Certificate Number</div><div class="r mono">#${escapeHtml(record.certNo)}</div></div>
          <div class="row hi"><div class="l">Final CGPA</div><div class="cgpa">${escapeHtml(record.cgpa)}</div></div>
          <div class="row"><div class="l">Result Status</div><div class="status"><span class="dot" aria-hidden="true"></span><span>Graduated</span></div></div>
        </div>
      </div>

      <div class="foot">
        <div></div>
        <div class="verified">
          <div class="k">Verified on</div>
          <div class="v">${escapeHtml(verifiedOn)}</div>
        </div>
      </div>
    </div>
  </body>
</html>`;
}

function printCurrentResult() {
  if (!currentRecord) {
    alert("Search a student first.");
    return;
  }
  const w = window.open("", "_blank");
  if (!w) {
    alert("Popup blocked. Please allow popups and try again.");
    return;
  }
  w.document.open();
  w.document.write(buildResultHtml(currentRecord));
  w.document.close();
  w.onload = function () {
    w.focus();
    w.print();
  };
}

function downloadCurrentResult() {
  if (!currentRecord) {
    alert("Search a student first.");
    return;
  }
  const html = buildResultHtml(currentRecord);
  const blob = new Blob([html], { type: "text/html;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `verification_${currentRecord.studentId || "result"}.html`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

async function downloadCurrentPdf() {
  if (!currentRecord) {
    alert("Search a student first.");
    return;
  }

  const element = document.querySelector("#resultSection .result-paper");
  if (!element) {
    alert("Result section not found.");
    return;
  }

  if (typeof window.html2pdf === "undefined") {
    // Fallback: download HTML if the PDF library isn't available.
    downloadCurrentResult();
    return;
  }

  // Best effort: wait for images to be ready before rendering.
  const images = Array.from(element.querySelectorAll("img"));
  await Promise.all(
    images.map((img) => {
      if (img.complete) return Promise.resolve();
      return new Promise((resolve) => {
        img.addEventListener("load", resolve, { once: true });
        img.addEventListener("error", resolve, { once: true });
      });
    }),
  );

  const filename = `verification_${currentRecord.studentId || "result"}.pdf`;

  const options = {
    margin: 10,
    filename: filename,
    image: { type: "jpeg", quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
  };

  window
    .html2pdf()
    .set(options)
    .from(element)
    .save()
    .catch(() => {
      // If html2pdf fails for any reason, fall back to HTML download.
      downloadCurrentResult();
    });
}

async function fetchFromBackend(studentId) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 15000);
  try {
    const response = await fetch(apiEndpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ studentId: studentId }),
      signal: controller.signal,
    });
    const result = await response.json().catch(() => null);
    if (!response.ok || !result) {
      throw new Error("Backend unavailable");
    }
    return result;
  } finally {
    clearTimeout(timeoutId);
  }
}

async function loadFallbackRecords() {
  if (!fallbackRecordsPromise) {
    fallbackRecordsPromise = fetch(fallbackDataEndpoint, { cache: "no-store" })
      .then((response) => {
        if (!response.ok) throw new Error("Fallback data missing");
        return response.json();
      })
      .then((records) => {
        if (!Array.isArray(records)) throw new Error("Invalid fallback data");
        return records.map(normalizeRecord);
      })
      .catch(() => embeddedFallbackRecords.map(normalizeRecord));
  }

  return fallbackRecordsPromise;
}

async function findRecordInFallback(query) {
  const records = await loadFallbackRecords();
  const searchValue = normalizeQuery(query);
  return (
    records.find((record) => {
      return (
        normalizeQuery(record.studentId) === searchValue ||
        normalizeQuery(record.regNo) === searchValue
      );
    }) || null
  );
}

async function findRecord(query) {
  let backendError = null;

  if (window.location.protocol !== "file:") {
    try {
      const backendResult = await fetchFromBackend(query);
      if (backendResult.status === "success" && backendResult.data) {
        const backendRecord = normalizeRecord(backendResult.data);
        if (!backendRecord.photo) {
          const fallbackRecord = await findRecordInFallback(query);
          if (fallbackRecord && fallbackRecord.photo) {
            backendRecord.photo = fallbackRecord.photo;
          }
        }

        return {
          record: backendRecord,
          source: "backend",
        };
      }
      if (backendResult.status !== "not_found") {
        backendError = new Error(
          backendResult.message || "Backend unavailable",
        );
      }
    } catch (error) {
      backendError = error;
    }
  }

  const fallbackRecord = await findRecordInFallback(query);
  if (fallbackRecord) {
    return {
      record: fallbackRecord,
      source: "fallback",
    };
  }

  if (backendError) throw backendError;
  return { record: null, source: "none" };
}

if (form && input && loading) {
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const value = input.value.trim();
    if (!value) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton ? submitButton.textContent : "";

    const resultSection = document.getElementById("resultSection");
    if (resultSection) resultSection.classList.add("hidden");
    loading.style.display = "block";
    if (submitButton) submitButton.disabled = true;

    try {
      const result = await findRecord(value);
      if (!result.record) {
        alert("Student record not found");
        return;
      }

      renderRecord(result.record);
      if (result.source === "fallback") {
        console.info("No-PHP fallback mode: showing local student data.");
      }
    } catch (error) {
      const message =
        error.name === "AbortError"
          ? "Request timed out. Using local fallback failed or no matching record was found."
          : error.message || "Unable to fetch student record.";
      alert(message);
    } finally {
      loading.style.display = "none";
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText;
      }
    }
  });
}

if (menuToggle && mainNav) {
  menuToggle.addEventListener("click", function () {
    mainNav.classList.toggle("is-open");
  });

  mainNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", function () {
      mainNav.classList.remove("is-open");
    });
  });
}

if (printResultBtn) {
  printResultBtn.addEventListener("click", printCurrentResult);
}

if (downloadResultBtn) {
  downloadResultBtn.addEventListener("click", downloadCurrentPdf);
}
