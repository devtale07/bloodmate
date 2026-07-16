// AI Eligibility Tester - Client-side heuristic model
// This is a pre-screening tool and not medical advice.

(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('aiEligibilityForm');
    const resultEl = document.getElementById('aiResult');
    const simulateBtn = document.getElementById('simulateTracker');

    if (simulateBtn) simulateBtn.addEventListener('click', simulateTrackerData);
    if (form) form.addEventListener('submit', onSubmit);
  });

  function onSubmit(e) {
    e.preventDefault();
    const inputs = readInputs();

    const { eligible, score, reasons, suggestions } = predictEligibility(inputs);

    renderResult({ eligible, score, reasons, suggestions });
  }

  function readInputs() {
    const getN = (id) => {
      const v = document.getElementById(id)?.value;
      const n = v === '' || v == null ? null : Number(v);
      return Number.isFinite(n) ? n : null;
    };
    const getB = (id) => !!document.getElementById(id)?.checked;
    const getD = (id) => {
      const v = document.getElementById(id)?.value;
      return v ? new Date(v) : null;
    };

    return {
      age: getN('age'),
      weight: getN('weight'),
      resting_hr: getN('resting_hr'),
      sleep_hours: getN('sleep_hours'),
      systolic: getN('systolic'),
      diastolic: getN('diastolic'),
      temperature: getN('temperature'),
      last_donation: getD('last_donation'),
      recent_illness: getB('recent_illness'),
      on_antibiotics: getB('on_antibiotics'),
      pregnant_recent: getB('pregnant_recent')
    };
  }

  // Lightweight heuristic "model"
  function predictEligibility(x) {
    const reasons = [];
    const suggestions = [];

    // Hard deferrals
    let hardDeferral = false;

    if (x.age == null || x.weight == null) {
      reasons.push('Please provide age and weight.');
      suggestions.push('Fill in the required fields and try again.');
      return { eligible: false, score: 0, reasons, suggestions };
    }

    if (x.age < 18 || x.age > 65) {
      hardDeferral = true;
      reasons.push('Age outside 18–65 eligibility range.');
      suggestions.push('Only donors aged 18–65 are eligible.');
    }

    if (x.weight < 50) {
      hardDeferral = true;
      reasons.push('Weight below 50kg minimum requirement.');
      suggestions.push('Ensure your weight is ≥ 50kg before donating.');
    }

    if (x.recent_illness) {
      hardDeferral = true;
      reasons.push('Recent illness in the last 14 days.');
      suggestions.push('Wait until fully recovered and symptom-free for 14 days.');
    }

    if (x.on_antibiotics) {
      hardDeferral = true;
      reasons.push('Currently on antibiotics.');
      suggestions.push('Wait until antibiotic course is complete and you are symptom-free.');
    }

    if (x.pregnant_recent) {
      hardDeferral = true;
      reasons.push('Pregnancy/recent donation (< 3 months) indicated.');
      suggestions.push('Wait at least 3 months post-pregnancy or last donation.');
    }

    // Last donation interval (56 days typical for whole blood)
    if (x.last_donation) {
      const today = new Date();
      const days = Math.floor((today - x.last_donation) / (1000 * 60 * 60 * 24));
      if (days < 56) {
        hardDeferral = true;
        reasons.push(`Last donation was ${days} days ago (needs ≥ 56 days).`);
        suggestions.push('Please wait until 56 days have passed from your last donation.');
      }
    }

    // Soft scoring (0..100)
    let score = 100;

    // Resting HR (50–90 ideal; <45 or >110 concerning)
    if (x.resting_hr != null) {
      if (x.resting_hr < 45 || x.resting_hr > 110) {
        score -= 35;
        reasons.push('Resting heart rate outside safe range (<45 or >110 bpm).');
        suggestions.push('Re-check when relaxed or consult a physician.');
      } else if (x.resting_hr < 50 || x.resting_hr > 90) {
        score -= 10;
        reasons.push('Heart rate slightly outside optimal 50–90 bpm.');
        suggestions.push('Rest for a few minutes and ensure you are hydrated.');
      }
    } else {
      score -= 5; // mild uncertainty penalty
    }

    // Sleep (>=6h is decent)
    if (x.sleep_hours != null) {
      if (x.sleep_hours < 5) {
        score -= 25;
        reasons.push('Very low sleep in the last 24h (<5h).');
        suggestions.push('Aim for at least 6–8 hours of rest before donating.');
      } else if (x.sleep_hours < 6) {
        score -= 10;
        reasons.push('Low sleep in the last 24h (<6h).');
      }
    }

    // Blood pressure (rough safe band 90–180 / 50–110; optimal ~ 100–130 / 60–85)
    if (x.systolic != null && x.diastolic != null) {
      if (x.systolic < 90 || x.systolic > 180 || x.diastolic < 50 || x.diastolic > 110) {
        score -= 35;
        reasons.push('Blood pressure outside safe range for donation.');
        suggestions.push('Measure again after rest; consult if persistently out of range.');
      } else {
        if (x.systolic < 100 || x.systolic > 140) score -= 5;
        if (x.diastolic < 60 || x.diastolic > 90) score -= 5;
      }
    } else if (x.systolic != null || x.diastolic != null) {
      score -= 5; // partial data penalty
    }

    // Temperature (36.0–37.5°C typical)
    if (x.temperature != null) {
      if (x.temperature < 36.0 || x.temperature > 37.8) {
        score -= 25;
        reasons.push('Body temperature outside normal range.');
        suggestions.push('If feverish or hypothermic, wait until normal temperature restores.');
      }
    }

    // Bound score
    score = Math.max(0, Math.min(100, Math.round(score)));

    // Final decision: any hard deferral => ineligible; otherwise require score >= 60
    const eligible = !hardDeferral && score >= 60;

    if (eligible && reasons.length === 0) {
      reasons.push('All key indicators within expected ranges.');
      suggestions.push('Proceed to donor registration at your nearest center.');
    }

    return { eligible, score, reasons, suggestions };
  }

  function renderResult({ eligible, score, reasons, suggestions }) {
    const el = document.getElementById('aiResult');
    if (!el) return;

    el.style.display = 'block';

    const statusIcon = eligible ? '<i class="fas fa-check-circle" style="color:#10b981"></i>' : '<i class="fas fa-times-circle" style="color:#ef4444"></i>';
    const statusText = eligible ? 'Likely Eligible to Donate' : 'Not Eligible Right Now';
    const statusColor = eligible ? '#10b981' : '#ef4444';

    const reasonsList = reasons.map(r => `<li>${escapeHtml(r)}</li>`).join('');
    const suggList = suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('');

    el.innerHTML = `
      <h3 style="display:flex;align-items:center;gap:.5rem;color:${statusColor}">${statusIcon} ${statusText} (Score: ${score}/100)</h3>
      ${reasonsList ? `<div style="margin-top:.5rem"><strong>Reasons</strong><ul>${reasonsList}</ul></div>` : ''}
      ${suggList ? `<div style="margin-top:.5rem"><strong>Suggestions</strong><ul>${suggList}</ul></div>` : ''}
      <p style="margin-top:.75rem;color:#6b7280;font-size:.9rem">This is a preliminary screening and not a medical decision. Final assessment is performed by trained staff.</p>
    `;
  }

  function simulateTrackerData() {
    // Generate plausible wellness metrics
    const rand = (min, max) => Math.round((Math.random() * (max - min) + min) * 10) / 10;
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };

    set('resting_hr', Math.round(rand(55, 75)));
    set('sleep_hours', rand(6, 8.5));
    set('systolic', Math.round(rand(105, 125)));
    set('diastolic', Math.round(rand(65, 80)));
    set('temperature', rand(36.3, 37.0));
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})();
