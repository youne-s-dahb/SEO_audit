// src/services/auditService.js
import api from './api';

// READ
export const fetchAuditPages = (params = {}) =>
  api.get('/api/audit_pages', { params });

export const fetchAuditPage = (id) =>
  api.get(`/api/audit_pages/${id}`);

export const fetchPageHeadings = (params = {}) =>
  api.get('/api/audit_page_headings', { params });

export const fetchPageHeading = (id) =>
  api.get(`/api/audit_page_headings/${id}`);

export const fetchPageImages = (params = {}) =>
  api.get('/api/audit_page_images', { params });

export const fetchPageImage = (id) =>
  api.get(`/api/audit_page_images/${id}`);

export const fetchKeywordDensities = (params = {}) =>
  api.get('/api/audit_keyword_densities', { params });

export const fetchKeywordDensity = (id) =>
  api.get(`/api/audit_keyword_densities/${id}`);

export const fetchAuditReports = (params = {}) =>
  api.get('/api/audit_reports', { params });

export const fetchAuditReport = (id) =>
  api.get(`/api/audit_reports/${id}`);

// WRITE / import endpoints (controllers fournis)
export const importAuditPage = (payload) =>
  api.post('/api/audit-page/import', payload);

export const importPageHeadings = (payload) =>
  api.post('/api/audit-page-heading/import', payload);

export const importPageImages = (payload) =>
  api.post('/api/audit-page-image/import', payload);

export const importKeywordDensity = (payload) =>
  api.post('/api/audit-keyword-density/import', payload);

export const generateReport = (auditId) =>
  api.post(`/api/audit-report/generate/${auditId}`);