"use client";

import { useEffect, useState } from "react";
import jobs from "../data/jobs";

const ITEMS_PER_PAGE = 10;

function formatNumber(value) {
  return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function timeAgo(dateString) {
  if (!dateString) return "Recently";
  const diff = Date.now() - new Date(dateString).getTime();
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return "Just now";
  if (minutes < 60) return `New ${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `New ${hours}h ago`;
  return `${Math.floor(hours / 24)}d ago`;
}

const WORK_ARRANGEMENTS = ["All", "Remote", "Hybrid", "On-site"];
const EXPERIENCE_LEVELS = ["All", "Entry level", "Mid-Senior level", "Senior level", "Director", "Internship"];
const EMPLOYMENT_TYPES = [
  { value: "All", label: "All" },
  { value: "FULL_TIME", label: "Full Time" },
  { value: "PART_TIME", label: "Part Time" },
  { value: "CONTRACT", label: "Contract" },
  { value: "INTERNSHIP", label: "Internship" },
];

function Pagination({ currentPage, totalPages, onPageChange }) {
  if (totalPages <= 1) return null;

  const getPageNumbers = () => {
    if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1);
    if (currentPage <= 4) return [1, 2, 3, 4, 5, "...", totalPages];
    if (currentPage >= totalPages - 3) return [1, "...", totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
    return [1, "...", currentPage - 1, currentPage, currentPage + 1, "...", totalPages];
  };

  return (
    <div className="pagination">
      <button
        className="page-btn"
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
      >
        &#8249;
      </button>
      {getPageNumbers().map((page, i) =>
        page === "..." ? (
          <span key={`ellipsis-${i}`} className="page-ellipsis">…</span>
        ) : (
          <button
            key={page}
            className={`page-btn ${currentPage === page ? "active" : ""}`}
            onClick={() => onPageChange(page)}
          >
            {page}
          </button>
        )
      )}
      <button
        className="page-btn"
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === totalPages}
      >
        &#8250;
      </button>
    </div>
  );
}

export default function HomePage() {
  const [query, setQuery] = useState("");
  const [jobsData, setJobsData] = useState(jobs);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [filters, setFilters] = useState({
    workArrangement: "All",
    experienceLevel: "All",
    employmentType: "All",
    location: "",
  });

  const handleSearch = async (searchTerm) => {
    const searchQuery = searchTerm.trim() || "AI";
    setLoading(true);
    setError("");
    setCurrentPage(1);

    try {
      const res = await fetch("/api/search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ query: searchQuery }),
      });

      const data = await res.json();
      if (!res.ok || data.error) {
        throw new Error(data.error || "Search failed.");
      }

      const results = Array.isArray(data.items) && data.items.length > 0 ? data.items : [];
      if (results.length === 0) {
        setError(`No results found for "${searchQuery}".`);
        setJobsData(jobs);
      } else {
        const normalizedJobs = results.map((job) => ({
          id: job.id || job.jobId || job.url || `${job.title}-${Math.random()}`,
          title: job.title || job.jobName || "Job opening",
          organization: job.organization || job.company || "Company",
          organizationLogo: job.organization_logo || job.organizationLogo || "",
          datePosted: job.date_posted || job.datePosted || new Date().toISOString(),
          locations: (job.locations_derived && job.locations_derived[0]) || job.locations || "United States",
          employmentType: Array.isArray(job.employment_type) ? job.employment_type[0] : job.employment_type || "FULL_TIME",
          aiExperienceLevel: job.ai_experience_level || job.seniority || "Mid-Senior level",
          aiWorkArrangement: job.ai_work_arrangement || job.work_arrangement || "Remote",
          aiKeySkills: job.ai_key_skills || ["AI", "Machine Learning", "Data"],
          descriptionHtml: job.description_html || job.description || "<p>No job description available.</p>",
        }));
        setJobsData(normalizedJobs);
      }
    } catch (err) {
      setError(err.message || "Unable to fetch jobs.");
      setJobsData(jobs);
    } finally {
      setLoading(false);
    }
  };

  const filteredJobs = jobsData.filter((job) => {
    if (filters.workArrangement !== "All" && job.aiWorkArrangement !== filters.workArrangement) return false;
    if (filters.experienceLevel !== "All" && job.aiExperienceLevel !== filters.experienceLevel) return false;
    if (filters.employmentType !== "All" && job.employmentType !== filters.employmentType) return false;
    if (filters.location && !job.locations.toLowerCase().includes(filters.location.toLowerCase())) return false;
    return true;
  });

  const totalPages = Math.ceil(filteredJobs.length / ITEMS_PER_PAGE);
  const pagedJobs = filteredJobs.slice((currentPage - 1) * ITEMS_PER_PAGE, currentPage * ITEMS_PER_PAGE);

  useEffect(() => {
    setCurrentPage(1);
  }, [filters, jobsData]);

  const hasActiveFilters =
    filters.workArrangement !== "All" ||
    filters.experienceLevel !== "All" ||
    filters.employmentType !== "All" ||
    filters.location !== "";

  const clearFilters = () =>
    setFilters({ workArrangement: "All", experienceLevel: "All", employmentType: "All", location: "" });

  const setFilter = (key, value) => setFilters((f) => ({ ...f, [key]: value }));

  return (
    <div>
      <section className="hero">
        <img
          src="/logo-authentic-scaled.png"
          alt="Skilluence"
          className="hero-logo"
          onError={(event) => {
            event.currentTarget.style.display = "none";
          }}
        />
        <h1>
          Find Your <em>Visa-Sponsored</em>
          <br /> Dream Job in the U.S.
        </h1>
        <p className="hero-sub">
          Thousands of verified roles from top U.S. employers actively sponsoring H-1B,
          OPT, TN &amp; Green Cards — built for international talent.
        </p>
        <div className="hero-cta-row">
          <button className="hero-btn-primary" onClick={() => handleSearch(query)}>
            Search Jobs
          </button>
          <button
            className="hero-btn-secondary"
            onClick={() => window.open("https://skilluence.com/resume-audit", "_blank")}
          >
            Get Free Resume Audit
          </button>
          <button
            className="hero-btn-secondary"
            onClick={() => {
              document.cookie = "loggedIn=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
              window.location.href = "/login";
            }}
            style={{
              padding: "15px 34px",
              fontSize: "15px",
              fontWeight: "600",
              background: "rgba(255,255,255,0.10)",
              color: "white",
              border: "1.5px solid rgba(255,255,255,0.35)",
              borderRadius: "99px",
              cursor: "pointer",
              backdropFilter: "blur(8px)",
              transition: "background 0.2s, border-color 0.2s, transform 0.2s",
              fontFamily: "inherit",
            }}
            onMouseOver={(e) => {
              e.target.style.background = "rgba(255,255,255,0.18)";
              e.target.style.borderColor = "rgba(255,255,255,0.6)";
              e.target.style.transform = "translateY(-2px)";
            }}
            onMouseOut={(e) => {
              e.target.style.background = "rgba(255,255,255,0.10)";
              e.target.style.borderColor = "rgba(255,255,255,0.35)";
              e.target.style.transform = "";
            }}
          >
            Logout
          </button>
        </div>
      </section>

      <div className="hero-bottom-search-text" id="jobs-board">
        <h2>Search roles that sponsor your visa</h2>
        <p>Live Apify-powered job search with a modern Next.js interface.</p>
      </div>

      <main className="main-layout container">
        {/* Jobs List */}
        <section className="left-col">
          <div className="search-row">
            <div className="search-input-wrap">
              <svg className="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
              </svg>
              <input
                type="text"
                value={query}
                placeholder="Search & find your dream job"
                onChange={(event) => setQuery(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === "Enter") handleSearch(query);
                }}
              />
              <button className="btn btn-primary search-btn" onClick={() => handleSearch(query)}>
                Search
              </button>
            </div>
          </div>

          <div className="results-meta">
            <div className="results-count">
              {loading ? (
                "Searching jobs..."
              ) : (
                <>
                  Showing <strong>{filteredJobs.length}</strong> of <strong>{formatNumber(jobsData.length)}+</strong> jobs
                </>
              )}
            </div>
            {totalPages > 1 && (
              <div className="results-page-info">
                Page <strong>{currentPage}</strong> of <strong>{totalPages}</strong>
              </div>
            )}
          </div>

          {error ? (
            <div style={{ marginBottom: 16, padding: 12, borderRadius: 12, background: "#fff7ed", border: "1px solid #fed7aa", color: "#92400e" }}>
              {error}
            </div>
          ) : null}

          <div className="jobs-list" id="jobsList">
            {pagedJobs.length === 0 ? (
              <div className="job-card">
                <div style={{ padding: "32px 20px", textAlign: "center", color: "var(--text-muted)" }}>
                  No jobs match the selected filters.
                </div>
              </div>
            ) : (
              pagedJobs.map((job) => (
                <article key={job.id} className="job-card" data-id={job.id}>
                  <div className="jc-header">
                    <div className="jc-company-info">
                      {job.organizationLogo ? (
                        <div className="jc-logo">
                          <img src={job.organizationLogo} alt={job.organization} />
                        </div>
                      ) : null}
                      <div>
                        <div className="jc-company">{job.organization}</div>
                        <div className="jc-title">{job.title}</div>
                        <div className="jc-location">{job.locations}</div>
                      </div>
                    </div>
                    <div className="jc-time">{timeAgo(job.datePosted)}</div>
                  </div>
                  <div className="jc-meta">
                    <span>{job.aiExperienceLevel}</span>
                    <span>{job.employmentType.replace("_", " ")}</span>
                    <span>{job.aiWorkArrangement}</span>
                  </div>
                  <div className="jc-tags">
                    {job.aiKeySkills.map((skill) => (
                      <span key={skill} className="jtag">{skill}</span>
                    ))}
                  </div>
                </article>
              ))
            )}
          </div>

          <Pagination
            currentPage={currentPage}
            totalPages={totalPages}
            onPageChange={(page) => {
              setCurrentPage(page);
              document.getElementById("jobsList")?.scrollIntoView({ behavior: "smooth", block: "start" });
            }}
          />
        </section>

        {/* Filter Panel (right) */}
        <aside className="filter-aside">
          <div className="filter-panel">
            <div className="filter-panel-header">
              <span className="filter-panel-title">Filters</span>
              {hasActiveFilters && (
                <button className="filter-clear-btn" onClick={clearFilters}>
                  Clear All
                </button>
              )}
            </div>

            <div className="filter-section">
              <div className="filter-section-title">Location</div>
              <input
                type="text"
                className="filter-location-input"
                placeholder="City, State…"
                value={filters.location}
                onChange={(e) => setFilter("location", e.target.value)}
              />
            </div>

            <div className="filter-section">
              <div className="filter-section-title">Work Arrangement</div>
              {WORK_ARRANGEMENTS.map((option) => (
                <label key={option} className="filter-option">
                  <input
                    type="radio"
                    name="workArrangement"
                    checked={filters.workArrangement === option}
                    onChange={() => setFilter("workArrangement", option)}
                  />
                  <span>{option}</span>
                </label>
              ))}
            </div>

            <div className="filter-section">
              <div className="filter-section-title">Experience Level</div>
              {EXPERIENCE_LEVELS.map((option) => (
                <label key={option} className="filter-option">
                  <input
                    type="radio"
                    name="experienceLevel"
                    checked={filters.experienceLevel === option}
                    onChange={() => setFilter("experienceLevel", option)}
                  />
                  <span>{option}</span>
                </label>
              ))}
            </div>

            <div className="filter-section">
              <div className="filter-section-title">Employment Type</div>
              {EMPLOYMENT_TYPES.map(({ value, label }) => (
                <label key={value} className="filter-option">
                  <input
                    type="radio"
                    name="employmentType"
                    checked={filters.employmentType === value}
                    onChange={() => setFilter("employmentType", value)}
                  />
                  <span>{label}</span>
                </label>
              ))}
            </div>
          </div>
        </aside>
      </main>
    </div>
  );
}
