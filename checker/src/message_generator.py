import random

time_periods = {
    "Ancient Times": (1800, 1850),
    "Industrial Revolution": (1850, 1900),
    "Early 20th Century": (1900, 1950),
    "Mid 20th Century": (1950, 1980),
    "Late 20th Century": (1980, 2000),
    "Early 21st Century": (2000, 2030),
    "Near Future": (2030, 2100),
    "Far Future": (2100, 2200)
}

warning_messages = [
    "Attention all time travelers! Temporal anomalies detected in sector {sector}. Proceed with extreme caution.",
    "WARNING: Paradox cascade imminent! All temporal displacement activities must cease immediately.",
    "ALERT: Butterfly effect detected in timeline {timeline}. Emergency protocols now in effect.",
    "Caution: Time vortex instability reported. Recommend postponing all non-essential chronojumps.",
    "URGENT: Grandfather paradox prevention system activated. Check your family trees before traveling!",
    "Notice: Temporal police have been dispatched to investigate unauthorized timeline modifications.",
    "DANGER: Reality fabric showing signs of wear. Please mind the gaps in spacetime.",
    "Alert: Causality loop detected. If you receive this message, don't send it to yourself.",
    "WARNING: Time storm approaching. Seek shelter in your nearest temporal bunker.",
    "Emergency: The Bootstrap Paradox is spreading! Quarantine all self-causing events immediately."
]

discovery_messages = [
    "Remarkable discovery! We've found evidence of {artifact} in the {location}. Time travel research accelerating!",
    "BREAKTHROUGH: New temporal technology allows travel to {year} with 99.7% accuracy!",
    "Scientific marvel: Quantum archaeologists have uncovered {discovery} from the {era}.",
    "Incredible findings: The lost {object} of {scientist} has been recovered from {year}!",
    "EUREKA! We've solved the {mystery} that has puzzled chronoscientists for decades.",
    "Amazing news: First successful communication established with {year} via temporal telegraph!",
    "Historical sensation: {person} was actually a time traveler! Evidence found in {year}.",
    "Extraordinary: We've decoded the temporal coordinates hidden in {ancient_text}.",
    "Revolutionary: New chrono-fuel increases time machine efficiency by {percentage}%!",
    "Astounding: Parallel timeline discovered where {alternate_history} actually happened!"
]

mission_messages = [
    "Mission briefing: Operatives needed for expedition to {year}. Objective: {mission_goal}.",
    "Recruitment notice: Seeking qualified chrononauts for dangerous assignment in {era}.",
    "Operation {codename} is a go! Rendezvous at temporal coordinates {coordinates} in {year}.",
    "Calling all time agents: Priority mission to prevent {disaster} in {year}. Apply immediately.",
    "Special assignment: Historical figure {person} requires protection during {event} in {year}.",
    "Urgent mission: Retrieve {important_item} before it's lost to history in {year}.",
    "Covert operation: Infiltrate {organization} in {year} to gather intelligence on {secret}.",
    "Time Corps deployment: Squad needed to ensure {historical_event} occurs as planned.",
    "Critical mission: Repair temporal fracture caused by rogue traveler in {year}.",
    "Classified operation: Details available only to agents with {clearance_level} clearance or higher."
]

announcement_messages = [
    "Congratulations to our coding challenge participants! Your algorithms are helping stabilize the timeline.",
    "New temporal regulations now in effect: All time machines must display current chronometer readings.",
    "Reminder: The Annual Time Travelers Convention has been rescheduled to last Tuesday. Again.",
    "Update: Temporal maintenance scheduled for {date}. Expect minor chronological hiccups.",
    "Achievement unlocked: Our scientists have successfully prevented their 1,000th paradox!",
    "Public service announcement: Lost time? Check the Department of Temporal Affairs.",
    "Celebration notice: Today marks the {anniversary} anniversary of time travel's invention!",
    "Important: New time travel license requirements now include a comprehensive butterfly effect test.",
    "Announcement: The Time Academy is accepting applications for next semester. All eras welcome!",
    "Milestone: Our quantum computer has solved {number} temporal paradoxes simultaneously!"
]

philosophical_messages = [
    "Remember: The past is a foreign country, but we have passports.",
    "Wisdom from {year}: '{quote}' - These words ring true across all timelines.",
    "Temporal thought: If you could change one moment in history, would you? Should you?",
    "Philosophy corner: Time is not a river, but an ocean. Navigate wisely.",
    "Reflection: Every decision creates a new branch in the tree of time. Choose your branches carefully.",
    "Ancient wisdom: '{proverb}' - Still relevant {time_span} years later.",
    "Paradox pondering: Can you truly learn from history if you can change it?",
    "Time traveler's dilemma: Is it better to know the future or to shape it?",
    "Chronological contemplation: Yesterday's impossibility is tomorrow's history.",
    "Temporal truth: The only constant in time travel is that nothing is constant."
]

sectors = ["Alpha-7", "Beta-Prime", "Gamma-Centauri", "Delta-X", "Epsilon-9", "Zeta-Temporal", "Theta-Quantum"]
timelines = ["Prime", "Alpha", "Beta-2", "Gamma-X", "Mirror", "Shadow", "Parallel-7", "Quantum-1"]
artifacts = ["Chronos Medallion", "Temporal Codex", "Quantum Crystal", "Time Compass", "Flux Capacitor", "Causality Engine"]
locations = ["Ancient Library of Alexandria", "Tesla's Laboratory", "Mayan Temple", "Victorian London", "Medieval Castle", "Space Station Omega"]
discoveries = ["lost civilization", "temporal equation", "time crystal cache", "chronological map", "quantum blueprint"]
eras = ["Jurassic Period", "Renaissance", "Atomic Age", "Victorian Era", "Space Age", "Digital Revolution"]
objects = ["Time Machine", "Temporal Beacon", "Chronometer", "Quantum Device", "Portal Generator", "Reality Anchor"]
scientists = ["Dr. Emmett Brown", "Professor Chronos", "Dr. Tesla", "Professor H.G. Wells", "Dr. Who", "Einstein"]
mysteries = ["Bermuda Triangle phenomenon", "Stonehenge construction", "Mayan disappearance", "Roswell incident", "Atlantis location"]
persons = ["Leonardo da Vinci", "Nikola Tesla", "Albert Einstein", "Marie Curie", "Benjamin Franklin", "Archimedes"]
ancient_texts = ["Dead Sea Scrolls", "Voynich Manuscript", "Sumerian Tablets", "Mayan Codex", "Egyptian Hieroglyphs"]
missions = ["prevent timeline collapse", "recover lost artifact", "protect historical figure", "gather intelligence", "repair temporal rift"]
codenames = ["Phoenix", "Chronos", "Paradox", "Nexus", "Quantum", "Eclipse", "Infinity", "Genesis"]
coordinates = ["X-47.2", "Y-88.5", "Z-15.7", "T-99.9", "Q-33.3", "R-66.6", "S-11.1", "P-77.7"]
disasters = ["the Great Fire", "volcanic eruption", "plague outbreak", "meteor impact", "nuclear accident", "alien invasion"]
events = ["signing of peace treaty", "scientific discovery", "royal coronation", "historic speech", "invention unveiling"]
organizations = ["Time Guardians", "Temporal Society", "Chronos Institute", "Quantum Council", "Time Lords", "Causality Corps"]
secrets = ["time travel technology", "future knowledge", "temporal coordinates", "paradox prevention", "timeline maps"]
historical_events = ["moon landing", "Renaissance begins", "printing press invented", "electricity discovered"]
clearance_levels = ["Alpha", "Beta", "Gamma", "Delta", "Temporal", "Quantum", "Classified", "Ultra"]
quotes = [
    "Time is but a river in which we cannot step twice",
    "The future belongs to those who prepare for it in the past",
    "Yesterday's science fiction is today's reality",
    "Time waits for no one, but sometimes we can catch up",
    "The past is written, but the future is still being authored"
]
proverbs = [
    "A stitch in time saves nine",
    "Time heals all wounds",
    "Lost time is never found again",
    "Time and tide wait for no man",
    "Better three hours too soon than a minute too late"
]
alternate_histories = ["dinosaurs never went extinct", "the Library of Alexandria never burned", "electricity was discovered in ancient Rome", "humans developed time travel in 1850"]

def generate_random_year():
    """Generate a random year between 1800 and 2200"""
    return random.randint(1800, 2200)

def generate_year_from_period(period_name=None):
    """Generate a year from a specific time period or random period"""
    if period_name and period_name in time_periods:
        start, end = time_periods[period_name]
        return random.randint(start, end)
    else:
        period = random.choice(list(time_periods.keys()))
        start, end = time_periods[period]
        return random.randint(start, end)

def customize_message(template):
    """Replace placeholders in message templates with random values"""
    return template.format(
        sector=random.choice(sectors),
        timeline=random.choice(timelines),
        artifact=random.choice(artifacts),
        location=random.choice(locations),
        discovery=random.choice(discoveries),
        era=random.choice(eras),
        object=random.choice(objects),
        scientist=random.choice(scientists),
        mystery=random.choice(mysteries),
        person=random.choice(persons),
        ancient_text=random.choice(ancient_texts),
        mission_goal=random.choice(missions),
        codename=random.choice(codenames),
        coordinates=random.choice(coordinates),
        disaster=random.choice(disasters),
        event=random.choice(events),
        organization=random.choice(organizations),
        secret=random.choice(secrets),
        historical_event=random.choice(historical_events),
        clearance_level=random.choice(clearance_levels),
        year=generate_random_year(),
        date=f"temporal day {random.randint(1, 365)}",
        anniversary=random.choice(["10th", "25th", "50th", "100th", "500th"]),
        number=random.randint(100, 9999),
        percentage=random.randint(50, 300),
        quote=random.choice(quotes),
        proverb=random.choice(proverbs),
        time_span=random.randint(100, 2000),
        alternate_history=random.choice(alternate_histories),
        important_item=random.choice(artifacts)
    )

def generate_admin_message():
    """Generate a complete admin message with text and year"""
    message_categories = [warning_messages, discovery_messages, mission_messages, announcement_messages, philosophical_messages]
    category = random.choice(message_categories)
    
    # Select and customize message
    template = random.choice(category)
    message_text = customize_message(template)
    
    # Generate appropriate year based on message content
    if category in [warning_messages, mission_messages]:
        year = random.choice([
            generate_year_from_period("Late 20th Century"),
            generate_year_from_period("Early 21st Century"),
            generate_year_from_period("Near Future"),
            generate_year_from_period("Far Future")
        ])
    elif category == discovery_messages:
        periods = ["Industrial Revolution", "Early 20th Century", "Mid 20th Century", "Near Future"]
        selected_period = random.choice(periods)
        year = generate_year_from_period(selected_period)
    else:
        year = generate_random_year()
    
    return {
        "message": message_text,
        "year": year
    }

def generate_urgent_message():
    """Generate an urgent/warning type message"""
    template = random.choice(warning_messages)
    message_text = customize_message(template)
    year = generate_year_from_period("Near Future")
    
    return {
        "message": message_text,
        "year": year
    }

def generate_historical_message():
    """Generate a message that appears to be from the past"""
    categories = [discovery_messages, philosophical_messages]
    template = random.choice(random.choice(categories))
    message_text = customize_message(template)
    
    past_periods = ["Ancient Times", "Industrial Revolution", "Early 20th Century", "Mid 20th Century"]
    selected_period = random.choice(past_periods)
    year = generate_year_from_period(selected_period)
    
    return {
        "message": message_text,
        "year": year
    }

def generate_futuristic_message():
    """Generate a message that appears to be from the future"""
    categories = [warning_messages, discovery_messages, mission_messages]
    template = random.choice(random.choice(categories))
    message_text = customize_message(template)
    
    future_periods = ["Near Future", "Far Future"]
    selected_period = random.choice(future_periods)
    year = generate_year_from_period(selected_period)
    
    return {
        "message": message_text,
        "year": year
    } 