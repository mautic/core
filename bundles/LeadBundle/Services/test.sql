SELECT * FROM MauticLeadBundle:Lead l LEFT JOIN email_stats es ON l.id = es.lead_id INNER JOIN page_hits ph ON l.id = ph.lead_id and ph.date_hit > "2017-09-10 23:14" GROUP BY l.id HAVING sum(es.open_count) > 0